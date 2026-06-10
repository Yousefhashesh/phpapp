<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipper extends Model
{
    protected $fillable = [
        'user_id',
        'commission_rate',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'shipper_user_id', 'user_id');
    }
}
