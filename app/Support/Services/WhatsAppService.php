<?php

namespace App\Support\Services;

use App\Models\ClientReturn;
use App\Models\ClientSettlement;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ShipperCollection;
use App\Models\ShipperReturn;
use App\Models\User;
use App\Observers\EntityObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function isEnabled(): bool
    {
        $value = Setting::getValue('whatsapp_enabled', config('whatsapp.enabled'));

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    public function sendGroupMessage(string $message): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $groupId = Setting::getValue('whatsapp_group_id', config('whatsapp.group_id'));
        if (! filled($groupId)) {
            return false;
        }

        $url = rtrim((string) Setting::getValue('whatsapp_service_url', config('whatsapp.service_url')), '/');

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Api-Secret' => (string) config('whatsapp.api_secret'),
                ])
                ->post("{$url}/send", [
                    'groupId' => $groupId,
                    'message' => $message,
                ]);

            if (! $response->successful()) {
                Log::warning('WhatsApp service error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('WhatsApp service unreachable', [
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function notifyOrderChange(Order $order, string $action, array $oldValues = [], array $newValues = []): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $order->loadMissing([
            'client:id,name',
            'shipper:id,name',
            'governorate:id,name',
            'city:id,name',
        ]);

        $actorName = $this->resolveActorName();
        $lines = [
            '📦 *سجل أوردر Shipya\n أهلا فريق العمل هذا الأوردر تم تحديثه *',
            '━━━━━━━━━━━━━━━━',
            "الكود: *#{$order->code}*",
            "الإجراء: {$this->resolveActionLabel($action, $oldValues, $newValues)}",
        ];

        if ($action === 'created') {
            $lines[] = "الحالة: {$order->status}";
            $lines[] = "المستلم: {$order->receiver_name}";
            $lines[] = "الموبايل: {$order->phone}";
            $lines[] = "العنوان: {$order->governorate?->name} - {$order->city?->name} - {$order->address}";
            $lines[] = "الإجمالي: {$order->total_amount} ج.م";
            $lines[] = "COD: {$order->cod_amount} ج.م";
        } else {
            foreach ($newValues as $field => $newValue) {
                if (in_array($field, EntityObserver::IGNORED_COLUMNS, true)) {
                    continue;
                }

                $oldValue = $oldValues[$field] ?? '—';
                $lines[] = $this->formatFieldChange($order, $field, $oldValue, $newValue);
            }
        }
       
        
        $lines[] = "العميل: ".($order->client?->name ?? '—');
        $lines[] = " هاتف العميل  : ".($order->client?->phone ?? '—');
         $lines[] = "العنوان   :".($order->address ?? '—');
         $lines[] = "ملاحظات حالة الاوردر :".($order->latest_status_note ?? '—');
        $lines[] = "ملاحظات   :".($order->note ?? '—');
        $lines[] = "المندوب: ".($order->shipper?->name ?? '—');
        $lines[] = "بواسطة: {$actorName}";
        $lines[] = 'الوقت: '.now()->timezone(config('app.timezone'))->format('d/m/Y H:i');

        $this->sendGroupMessage(implode("\n", array_filter($lines)));
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function notifyWorkflowEvent(Model $model, string $action, array $oldValues = [], array $newValues = []): void
    {
        if (! $this->isEnabled() || ! $this->isSupportedWorkflowModel($model)) {
            return;
        }

        $this->loadWorkflowRelations($model);

        $lines = [
            '*Shipya workflow update*',
            '------------------------------',
            'Type: '.$this->workflowTypeLabel($model),
            'Action: '.$this->workflowActionLabel($action, $newValues),
            'Record: #'.$model->getKey(),
            'Status: '.($model->status ?? '-'),
            'Approval: '.($model->approval_status ?? '-'),
            'Orders: '.($model->number_of_orders ?? '-'),
        ];

        $ownerName = $this->workflowOwnerName($model);
        if ($ownerName !== null) {
            $lines[] = 'Account: '.$ownerName;
        }

        if ($model instanceof ShipperCollection || $model instanceof ClientSettlement) {
            $lines[] = 'Total: '.($model->total_amount ?? '0').' EGP';
            $lines[] = 'Fees: '.($model instanceof ShipperCollection ? ($model->shipper_fees ?? '0') : ($model->fees ?? '0')).' EGP';
            $lines[] = 'Net: '.($model->net_amount ?? '0').' EGP';
        }

        if ($action === 'updated') {
            foreach ($newValues as $field => $newValue) {
                if (in_array($field, EntityObserver::IGNORED_COLUMNS, true)) {
                    continue;
                }

                $oldValue = $oldValues[$field] ?? '-';
                $lines[] = "{$field}: {$oldValue} -> {$newValue}";
            }
        }

        $lines[] = 'By: '.$this->resolveActorName();
        $lines[] = 'Time: '.now()->timezone(config('app.timezone'))->format('d/m/Y H:i');

        $this->sendGroupMessage(implode("\n", array_filter($lines)));
    }

    private function resolveActorName(): string
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->name ?? 'النظام';
    }

    private function isSupportedWorkflowModel(Model $model): bool
    {
        return $model instanceof ShipperCollection
            || $model instanceof ClientSettlement
            || $model instanceof ShipperReturn
            || $model instanceof ClientReturn;
    }

    private function loadWorkflowRelations(Model $model): void
    {
        if ($model instanceof ShipperCollection || $model instanceof ShipperReturn) {
            $model->loadMissing(['shipper:id,name']);
        }

        if ($model instanceof ClientSettlement || $model instanceof ClientReturn) {
            $model->loadMissing(['client:id,name']);
        }
    }

    private function workflowTypeLabel(Model $model): string
    {
        return match (true) {
            $model instanceof ShipperCollection => 'Shipper collection',
            $model instanceof ClientSettlement => 'Client settlement',
            $model instanceof ShipperReturn => 'Shipper return',
            $model instanceof ClientReturn => 'Client return',
            default => class_basename($model),
        };
    }

    private function workflowActionLabel(string $action, array $newValues): string
    {
        if ($action === 'created') {
            return 'Created';
        }

        if ($action === 'deleted') {
            return 'Deleted';
        }

        if (array_key_exists('approval_status', $newValues)) {
            return 'Approval changed to '.$newValues['approval_status'];
        }

        if (array_key_exists('status', $newValues)) {
            return 'Status changed to '.$newValues['status'];
        }

        return 'Updated';
    }

    private function workflowOwnerName(Model $model): ?string
    {
        return match (true) {
            $model instanceof ShipperCollection, $model instanceof ShipperReturn => $model->shipper?->name,
            $model instanceof ClientSettlement, $model instanceof ClientReturn => $model->client?->name,
            default => null,
        };
    }

    private function resolveActionLabel(string $action, array $oldValues, array $newValues): string
    {
        if ($action === 'created') {
            return 'إنشاء أوردر جديد';
        }

        if (array_key_exists('status', $newValues)) {
            $old = $oldValues['status'] ?? '—';
            $new = $newValues['status'] ?? '—';

            return "تغيير الحالة ({$old} → {$new})";
        }

        return 'تحديث بيانات الأوردر';
    }

    private function formatFieldChange(Order $order, string $field, mixed $oldValue, mixed $newValue): string
    {
        return match ($field) {
            'status' => "الحالة: {$oldValue} → {$newValue}",
            'total_amount' => "الإجمالي: {$oldValue} → {$newValue} ج.م",
            'cod_amount' => "COD: {$oldValue} → {$newValue} ج.م",
            'shipping_fee' => "الشحن: {$oldValue} → {$newValue} ج.م",
            'commission_amount' => "العمولة: {$oldValue} → {$newValue} ج.م",
            'company_amount' => "صافي الشركة: {$oldValue} → {$newValue} ج.م",
            'latest_status_note' => "ملاحظة الحالة: {$newValue}",
            'order_note' => "ملاحظة الأوردر: {$newValue}",
            'receiver_name' => "المستلم: {$oldValue} → {$newValue}",
            'phone' => "الموبايل: {$oldValue} → {$newValue}",
            'is_shipper_collected' => 'تحصيل المندوب: '.($newValue ? 'نعم' : 'لا'),
            'is_client_settled' => 'تسوية العميل: '.($newValue ? 'نعم' : 'لا'),
            'is_shipper_returned' => 'مرتجع المندوب: '.($newValue ? 'نعم' : 'لا'),
            'is_client_returned' => 'مرتجع العميل: '.($newValue ? 'نعم' : 'لا'),
            'shipper_user_id' => 'تم تعيين مندوب جديد',
            default => "{$field}: {$oldValue} → {$newValue}",
        };
    }
}
