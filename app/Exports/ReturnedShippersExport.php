<?php

namespace App\Exports;

use App\Models\ShipperReturn;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class ReturnedShippersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
            $query = ShipperReturn::query()
                ->with(['shipper', 'orders.client', 'orders.governorate', 'orders.city'])
                ->whereIn('id', $this->ids)
                ->latest();
        } else {
            $query = $this->query
                ? $this->query->with(['shipper', 'orders.client', 'orders.governorate', 'orders.city'])
                : ShipperReturn::query()
                    ->with(['shipper', 'orders.client', 'orders.governorate', 'orders.city'])
                    ->latest();
        }

        $returns = $query->get();

        $rows = collect();
        foreach ($returns as $return) {
            foreach ($return->orders as $order) {
                $rows->push((object) [
                    'id' => $return->id,
                    'date' => $return->return_date?->format('Y-m-d'),
                    'shipper' => $return->shipper?->name,
                    'order_code' => $order->code,
                    'client' => $order->client?->name,
                    'receiver' => $order->receiver_name,
                    'phone' => $order->phone,
                    'area' => $order->governorate?->name . ' - ' . $order->city?->name,
                    'status' => $order->status,
                    'note' => $order->latest_status_note,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'رقم المرتجع',
            'التاريخ',
            'المندوب',
            'كود الأوردر',
            'العميل',
            'المستلم',
            'رقم الهاتف',
            'المنطقة',
            'الحالة',
            'ملاحظات الحالة',
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
            $row->status,
            $row->note,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EA5455'], // Red for returns
                ],
            ],
        ];
    }
}
