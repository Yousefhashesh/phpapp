<?php

namespace App\Exports;

use App\Models\ClientSettlement;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class CollectedClientsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
            $query = ClientSettlement::query()
                ->with(['client', 'orders.client', 'orders.governorate', 'orders.city', 'orders.shipper'])
                ->whereIn('id', $this->ids)
                ->latest();
        } else {
            $query = $this->query
                ? $this->query->with(['client', 'orders.client', 'orders.governorate', 'orders.city', 'orders.shipper'])
                : ClientSettlement::query()
                    ->with(['client', 'orders.client', 'orders.governorate', 'orders.city', 'orders.shipper'])
                    ->latest();
        }

        $settlements = $query->get();

        $rows = collect();
        foreach ($settlements as $settlement) {
            foreach ($settlement->orders as $order) {
                $rows->push((object) [
                    'id' => $settlement->id,
                    'date' => $settlement->settlement_date?->format('Y-m-d'),
                    'client' => $settlement->client?->name,
                    'order_code' => $order->code,
                    'receiver' => $order->receiver_name,
                    'phone' => $order->phone,
                    'area' => $order->governorate?->name . ' - ' . $order->city?->name,
                    'shipper' => $order->shipper?->name,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
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
            'رقم التسوية',
            'التاريخ',
            'العميل',
            'كود الأوردر',
            'المستلم',
            'رقم الهاتف',
            'المنطقة',
            'المندوب',
            'حالة الأوردر',
            'الإجمالي',
            'مصاريف الشحن',
            'الصافي (COD)',
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
            $row->total_amount,
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
                    'startColor' => ['rgb' => '10B981'], // Green for settlements
                ],
            ],
        ];
    }
}
