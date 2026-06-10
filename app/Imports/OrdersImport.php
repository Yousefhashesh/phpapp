<?php

namespace App\Imports;

use App\Models\Order;
use App\Models\Governorate;
use App\Models\City;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Client;
use App\Models\PlanPrice;
use App\Models\Shipper;

class OrdersImport implements WithMultipleSheets, SkipsUnknownSheets
{
    protected $userId;
    protected $successCount = 0;
    protected $errors = [];

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function sheets(): array
    {
        // Support up to 10 sheets, skipping the "Data" ones or unknown ones
        $sheets = [];
        for ($i = 0; $i < 10; $i++) {
            $sheets[$i] = new OrdersImportSheet($this->userId, $this);
        }
        return $sheets;
    }

    public function onUnknownSheet($sheetName)
    {
        // Silently skip unknown sheets (fixes "out of bounds" error)
    }

    public function addSuccess($count = 1)
    {
        $this->successCount += $count;
    }

    public function addErrors(array $errors)
    {
        $this->errors = array_merge($this->errors, $errors);
    }

    public function getResults()
    {
        return [
            'success_count' => $this->successCount,
            'errors' => $this->errors,
        ];
    }
}

class OrdersImportSheet implements ToCollection, WithHeadingRow
{
    protected $userId;
    protected $parent;

    public function __construct($userId, $parent)
    {
        $this->userId = $userId;
        $this->parent = $parent;
    }

    private function normalizeArabic($text)
    {
        if (!$text) return '';
        $text = trim((string) $text);
        $text = str_replace(['أ', 'إ', 'آ'], 'ا', $text);
        $text = str_replace('ة', 'ه', $text);
        $text = str_replace(['ى', 'ئ', 'ؤ'], 'ي', $text);
        return $text;
    }

    /**
     * Find a value in a row by checking keys against multiple possible versions or substrings.
     * Normalizes both the keys and the keywords for robust matching.
     */
    private function getRowValue(Collection $row, array $keywords, $default = null)
    {
        $keys = $row->keys();
        $normalizedKeywords = array_map([$this, 'normalizeArabic'], $keywords);

        foreach ($normalizedKeywords as $nKeyword) {
            foreach ($keys as $key) {
                $nKey = $this->normalizeArabic((string)$key);
                // Check if key contains keyword OR keyword contains key (for short slugs)
                if (str_contains($nKey, $nKeyword) || str_contains($nKeyword, $nKey)) {
                    $val = $row->get($key);
                    return is_null($val) ? null : trim((string)$val);
                }
            }
        }
        return $default;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) return;

        // Diagnostic: If the headers look like the "Data" sheet reference columns, skip it.
        $headers = $rows->first()->keys()->toArray();
        $isDataSheet = false;
        foreach ($headers as $h) {
            $nh = $this->normalizeArabic((string)$h);
            if (str_contains($nh, 'safename') || str_contains($nh, 'clients') || str_contains($nh, 'almhafth')) {
                $isDataSheet = true;
                break;
            }
        }

        if ($isDataSheet) {
            Log::info("Skipping 'Data' sheet during import.");
            return;
        }

        // Check if Sheet has any order-like columns (Phone, Receiver, Name)
        $isOrderSheet = false;
        $orderKeywords = ['phone', 'receiver', 'address', 'als_ar', 'price', 'الاسم', 'الرقم'];
        foreach ($headers as $h) {
            $nh = $this->normalizeArabic((string)$h);
            foreach ($orderKeywords as $k) {
                if (str_contains($nh, $this->normalizeArabic($k))) {
                    $isOrderSheet = true;
                    break 2;
                }
            }
        }

        if (!$isOrderSheet) {
            return;
        }

        // 1. Prepare Governorates and Cities
        $governorates = [];
        $govNames = []; // To map ID back to Name for fallback
        foreach (Governorate::all() as $gov) {
            $nName = $this->normalizeArabic($gov->name);
            $governorates[$nName] = $gov->id;
            $govNames[$gov->id] = $nName;
        }

        $allCities = City::all();
        $cities = [];
        foreach ($allCities as $city) {
            $normalizedCityName = $this->normalizeArabic($city->name);
            $cities[$city->governorate_id][$normalizedCityName] = $city->id;
        }

        $sheetErrors = [];

        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2;

            // Check if Row has data
            $hasData = $row->filter(fn($v) => !is_null($v) && trim((string)$v) !== '')->count() > 0;
            if (!$hasData) continue;

            try {
                // 1. Extract Client ID
                $clientInfo = $this->getRowValue($row, ['id_client', 'client', 'amyl', 'العميل', 'amyl_id', 'vendor', 'mws_l', 'id_almyl', 'kwd_alamyl', 'alamyl']);
                $clientUserId = null;

                if ($clientInfo) {
                    if (preg_match('/\(([^)]+)\)/', (string)$clientInfo, $matches)) {
                        $clientUserId = (int) $matches[1];
                    } elseif (preg_match('/\[([^\]]+)\]/', (string)$clientInfo, $matches)) {
                        $clientUserId = (int) $matches[1];
                    } elseif (is_numeric($clientInfo)) {
                        $clientUserId = (int) $clientInfo;
                    }
                }

                if (!$clientUserId) {
                    $availableKeys = implode(', ', $row->keys()->toArray());
                    $sheetErrors[] = "Row {$lineNumber}: Client info not found or invalid. Got: '{$clientInfo}'. Headers: [{$availableKeys}]";
                    continue;
                }

                // 2. Validate Governorate & City
                $govInput = trim((string)$this->getRowValue($row, ['governorate', 'mhafz', 'المحافظة', 'almhafzh', 'almhfth'], ''));
                $govNormalized = $this->normalizeArabic($govInput);
                $govId = $governorates[$govNormalized] ?? null;

                if (!$govId) {
                    $sheetErrors[] = "Row {$lineNumber}: Governorate '{$govInput}' not found.";
                    continue;
                }

                $cityInput = trim((string)$this->getRowValue($row, ['mntqh', 'المنطقة', 'city', 'area', 'almdynh', 'المدينة', 'hy', 'حي', 'almntkh', 'almntqh', 'almdyn'], ''));
                $cityNormalized = $this->normalizeArabic($cityInput);
                $cityId = $cities[$govId][$cityNormalized] ?? null;

                // Fallback: If city is blank, try to match current governorate name as city
                if (!$cityId && $cityInput === '') {
                    $fallbackCityName = $govNames[$govId] ?? null;
                    if ($fallbackCityName && isset($cities[$govId][$fallbackCityName])) {
                        $cityId = $cities[$govId][$fallbackCityName];
                    }
                }

                if (!$cityId) {
                    $sheetErrors[] = "Row {$lineNumber}: City '{$cityInput}' not found in Governorate '{$govInput}'.";
                    continue;
                }

                // Handle status
                $statusInput = strtoupper(trim((string)$this->getRowValue($row, ['alhalh', 'الحالة', 'status', 'alhal', 'albal'], '')));
                $status = 'OUT_FOR_DELIVERY';
                if (in_array($statusInput, ['DELIVERED', 'مستلم', 'تم التسليم', 'DONE', 'STLM'])) {
                    $status = 'DELIVERED';
                }

                // Handle Phone Splitting
                $phoneInput = trim((string)$this->getRowValue($row, ['alrqm', 'الرقم', 'alrkm', 'phone', 'tele', 'mob', 'alksm'], ''));
                $phone2Input = trim((string)$this->getRowValue($row, ['alrqm_altany', 'تاني', 'alrkm_altany', 'phone_2', 'phone2'], ''));
                if (str_contains($phoneInput, '-')) {
                    $parts = explode('-', $phoneInput);
                    $phoneInput = trim($parts[0]);
                    if (empty($phone2Input)) {
                        $phone2Input = trim($parts[1] ?? '');
                    }
                }

                // Clean Amount Output (remove spaces, symbols)
                $amountValue = (string)$this->getRowValue($row, ['als_ar', 'السعر', 'alsaar', 'price', 'total', 'amount', 'امونت', 'ts_ar', 'tsaar', 'alkym'], 0);
                $amountValue = preg_replace('/[^0-9.]/', '', $amountValue); // Keep only digits and dots

                // 3. Build data
                $orderData = [
                    'external_code' => $this->getRowValue($row, ['kwd', 'code', 'external', 'alshrkh', 'extra', 'shrk', 'الشركة', 'kwd_alshrkh', 'alshrkh']),
                    'client_user_id' => $clientUserId,
                    'receiver_name' => $this->getRowValue($row, ['name', 'asm', 'الاسم', 'receiver', 'alasm']),
                    'phone' => $phoneInput,
                    'phone_2' => $phone2Input === '' ? null : $phone2Input,
                    'governorate_id' => $govId,
                    'city_id' => $cityId,
                    'address' => $this->getRowValue($row, ['address', 'onwan', 'العنوان', 'al-onwan', 'alonan']),
                    'total_amount' => (float)$amountValue,
                    'order_note' => $this->getRowValue($row, ['almlhwzh', 'الملحوظة', 'note', 'almlhwzh', 'mlhwth'], null),
                    'status' => $status, 
                    'created_by' => $this->userId,
                    'approval_status' => 'APPROVED',
                ];

                $orderData = $this->resolveDefaultShipper($orderData);
                $orderData = $this->applyAutomaticFinancials($orderData);
                $orderData['code'] = Order::generateUniqueCode();

                if (!empty($orderData['shipper_user_id'])) {
                    $orderData['shipper_date'] = now()->toDateString();
                }

                Order::create($orderData);
                $this->parent->addSuccess();

            } catch (\Exception $e) {
                Log::error("Import Error Row {$lineNumber}: " . $e->getMessage());
                $errorMsg = $e->getMessage();
                if (str_contains($errorMsg, 'SQLSTATE[23000]')) {
                    $errorMsg = "Database Integrity Error (duplicate code or missing required field).";
                }
                $sheetErrors[] = "Row {$lineNumber}: {$errorMsg}";
            }
        }

        $this->parent->addErrors($sheetErrors);
    }

    private function applyAutomaticFinancials(array $data): array
    {
        $clientUserId = $data['client_user_id'];
        $governorateId = $data['governorate_id'];
        $shipperUserId = $data['shipper_user_id'] ?? null;
        $totalAmount = $data['total_amount'];

        $shippingFee = $this->resolveShippingFee((int) $clientUserId, (int) $governorateId);
        $commissionAmount = $this->resolveCommissionAmount($shipperUserId ? (int) $shipperUserId : null);
        $total = round((float) $totalAmount, 2);

        $data['total_amount'] = $total;
        $data['shipping_fee'] = round((float) $shippingFee, 2);
        $data['commission_amount'] = round((float) $commissionAmount, 2);
        $data['company_amount'] = round($data['shipping_fee'] - $data['commission_amount'], 2);
        $data['cod_amount'] = round($total - $data['shipping_fee'], 2);

        return $data;
    }

    private function resolveShippingFee(int $clientUserId, int $governorateId): float
    {
        $client = Client::where('user_id', $clientUserId)->first();
        if (!$client) return 0.0;

        $planId = $client->plan_id;
        if (!$planId) return 0.0;

        $price = PlanPrice::where('plan_id', $planId)
            ->where('governorate_id', $governorateId)
            ->value('price');

        return (float) ($price ?? 0.0);
    }

    private function resolveCommissionAmount(?int $shipperUserId): float
    {
        if ($shipperUserId === null) return 0;
        $shipper = Shipper::where('user_id', $shipperUserId)->first();
        return (float) ($shipper->commission_rate ?? 0);
    }

    private function resolveDefaultShipper(array $data): array
    {
        $governorateId = $data['governorate_id'];
        $defaultShipperUserId = Governorate::whereKey($governorateId)->value('default_shipper_user_id');
        if ($defaultShipperUserId) {
            $data['shipper_user_id'] = (int) $defaultShipperUserId;
        }
        return $data;
    }
}
