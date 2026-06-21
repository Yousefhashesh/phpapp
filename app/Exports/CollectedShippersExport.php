<?php

namespace App\Exports;

use App\Models\ShipperCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CollectedShippersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $query;
    protected ?array $ids;

    public function __construct($query = null, ?array $ids = null)
    {
        $this->query = $query;
        $this->ids = $ids;
    }

    public function collection(): Collection
    {
        $collections = $this->baseQuery()
            ->with([
                'shipper:id,name',
                'orders.client:id,name',
                'orders.governorate:id,name',
                'orders.city:id,name',
            ])
            ->get();

        $rows = collect();

        foreach ($collections as $collection) {
            foreach ($collection->orders as $order) {
                $rows->push((object) [
                    'id' => $collection->id,
                    'date' => $collection->collection_date?->format('Y-m-d'),
                    'shipper' => $collection->shipper?->name,
                    'order_code' => $order->code,
                    'client' => $order->client?->name,
                    'receiver' => $order->receiver_name,
                    'phone' => $order->phone,
                    'area' => trim(($order->governorate?->name ?? '').' - '.($order->city?->name ?? ''), ' -'),
                    'order_amount' => $order->total_amount,
                    'shipper_commission' => $order->commission_amount,
                    'net' => round((float) $order->total_amount - (float) $order->commission_amount, 2),
                    'order_status' => $order->status,
                    'approval_status' => $order->approval_status,
                    'order_note' => $order->order_note,
                    'latest_status_note' => $order->latest_status_note,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'رقم التحصيل',
            'التاريخ',
            'المندوب',
            'كود الاوردر',
            'العميل',
            'المستلم',
            'الهاتف',
            'المنطقة',
            'مبلغ الطلب',
            'عمولة المندوب',
            'الصافي',
            'حالة الاوردر',
            'الحالة',
            'ملاحظة الاوردر',
            'ملاحظة الحالة',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->date,
            $row->shipper,
            $row->order_code,
            $row->client,
            $row->receiver,
            $row->phone,
            $row->area,
            $row->order_amount,
            $row->shipper_commission,
            $row->net,
            $row->order_status,
            $row->approval_status,
            $row->order_note,
            $row->latest_status_note,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '7367F0'],
                ],
            ],
        ];
    }

    private function baseQuery()
    {
        if ($this->ids && count($this->ids) > 0) {
            return ShipperCollection::query()
                ->whereIn('id', $this->ids)
                ->latest();
        }

        return $this->query ?: ShipperCollection::query()->latest();
    }
}
