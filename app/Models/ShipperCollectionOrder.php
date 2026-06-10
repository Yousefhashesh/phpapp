<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipperCollectionOrder extends Model
{
    protected $fillable = [
        'shipper_collection_id',
        'order_id',
        'order_amount',
        'shipper_fee',
        'net_amount',
        'added_at',
    ];

    protected function casts(): array
    {
        return [
            'order_amount' => 'decimal:2',
            'shipper_fee'  => 'decimal:2',
            'net_amount'   => 'decimal:2',
            'added_at'     => 'datetime',
        ];
    }

    public function shipperCollection()
    {
        return $this->belongsTo(ShipperCollection::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
