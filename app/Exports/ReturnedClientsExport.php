<?php

namespace App\Exports;

use App\Models\ClientReturn;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class ReturnedClientsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
            $query = ClientReturn::query()
                ->with(['client', 'orders.client', 'orders.governorate', 'orders.city', 'orders.shipper'])
                ->whereIn('id', $this->ids)
                ->latest();
        } else {
            $query = $this->query
                ? $this->query->with(['client', 'orders.client', 'orders.governorate', 'orders.city', 'orders.shipper'])
                : ClientReturn::query()
                    ->with(['client', 'orders.client', 'orders.governorate', 'orders.city', 'orders.shipper'])
                    ->latest();
        }

        $returns = $query->get();

        $rows = collect();
        foreach ($returns as $return) {
            foreach ($return->orders as $order) {
                $rows->push((object) [
                    'id' => $return->id,
                    'date' => $return->return_date?->format('Y-m-d'),
                    'client' => $return->client?->name,
                    'order_code' => $order->code,
                    'receiver' => $order->receiver_name,
                    'phone' => $order->phone,
                    'area' => $order->governorate?->name . ' - ' . $order->city?->name,
                    'shipper' => $order->shipper?->name,
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
            'رقم كشف مرتجع العميل',
            'التاريخ',
            'العميل',
            'كود الأوردر',
            'المستلم',
            'رقم الهاتف',
            'المنطقة',
            'المندوب',
            'الحالة',
            'ملاحظات الحالة',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->date,
            $row->client,
            $row->order_code,
            $row->receiver,
            $row->phone,
            $row->area,
            $row->shipper,
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
                    'startColor' => ['rgb' => 'F59E0B'], // Orange for client returns
                ],
            ],
        ];
    }
}
