<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientSettlementOrder extends Model
{
    protected $fillable = [
        'client_settlement_id',
        'order_id',
        'order_amount',
        'fee',
        'net_amount',
        'added_at',
    ];

    protected function casts(): array
    {
        return [
            'order_amount' => 'decimal:2',
            'fee'          => 'decimal:2',
            'net_amount'   => 'decimal:2',
            'added_at'     => 'datetime',
        ];
    }

    public function clientSettlement()
    {
        return $this->belongsTo(ClientSettlement::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
