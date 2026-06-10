<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientReturnOrder extends Model
{
    protected $fillable = [
        'client_return_id',
        'order_id',
        'added_at',
    ];

    protected function casts(): array
    {
        return [
            'added_at' => 'datetime',
        ];
    }

    public function clientReturn()
    {
        return $this->belongsTo(ClientReturn::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
