<?php

namespace App\Exports;

use App\Models\ClientSettlement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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
        $settlements = $this->baseQuery()
            ->with([
                'client:id,name',
                'orders.governorate:id,name',
                'orders.city:id,name',
                'orders.shipper:id,name',
            ])
            ->get();

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
                    'area' => trim(($order->governorate?->name ?? '').' - '.($order->city?->name ?? ''), ' -'),
                    'shipper' => $order->shipper?->name,
                    'order_status' => $order->status,
                    'approval_status' => $order->approval_status,
                    'order_note' => $order->order_note,
                    'latest_status_note' => $order->latest_status_note,
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
            'كود التسوية',
            'التاريخ',
            'العميل',
            'كود الاوردر',
            'المستلم',
            'الهاتف',
            'المنطقة',
            'المندوب',
            'حالة الاوردر',
            'الحالة',
            'ملاحظة الاوردر',
            'ملاحظة الحالة',
            'الاجمالي',
            'الشحن',
            'الصافي',
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
            $row->order_status,
            $row->approval_status,
            $row->order_note,
            $row->latest_status_note,
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
                    'startColor' => ['rgb' => '10B981'],
                ],
            ],
        ];
    }

    private function baseQuery()
    {
        if ($this->ids && count($this->ids) > 0) {
            return ClientSettlement::query()
                ->whereIn('id', $this->ids)
                ->latest();
        }

        return $this->query ?: ClientSettlement::query()->latest();
    }
}
