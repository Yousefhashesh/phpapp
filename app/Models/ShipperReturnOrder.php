<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipperReturnOrder extends Model
{
    protected $fillable = [
        'shipper_return_id',
        'order_id',
        'added_at',
    ];

    protected function casts(): array
    {
        return [
            'added_at' => 'datetime',
        ];
    }

    public function shipperReturn()
    {
        return $this->belongsTo(ShipperReturn::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
