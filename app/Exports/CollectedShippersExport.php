<?php

namespace App\Exports;

use App\Models\ShipperCollection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

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
        $query = null;

        if ($this->ids && count($this->ids) > 0) {
            $query = ShipperCollection::query()
                ->with(['shipper', 'orders.client', 'orders.governorate', 'orders.city'])
                ->whereIn('id', $this->ids)
                ->latest();
        } else {
            $query = $this->query
                ? $this->query->with(['shipper', 'orders.client', 'orders.governorate', 'orders.city'])
                : ShipperCollection::query()
                    ->with(['shipper', 'orders.client', 'orders.governorate', 'orders.city'])
                    ->latest();
        }

        $collections = $query->get();

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
                    'area' => $order->governorate?->name . ' - ' . $order->city?->name,
                    'order_amount' => $order->total_amount,
                    'shipping_fee' => $order->shipping_fee,
                    'cod' => $order->cod_amount,
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
            'كود الأوردر',
            'العميل',
            'المستلم',
            'رقم الهاتف',
            'المنطقة',
            'قيمة الأوردر',
            'شحن',
            'صافي التحصيل',
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
            $row->shipping_fee,
            $row->cod,
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
}
