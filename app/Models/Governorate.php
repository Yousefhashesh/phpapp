<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Governorate extends Model
{
    protected $fillable = [
        'name',
        'follow_up_hours',
        'default_shipper_user_id',
    ];

    public function cities()
    {
        return $this->hasMany(City::class);
    }

    public function defaultShipper()
    {
        return $this->belongsTo(User::class, 'default_shipper_user_id');
    }

    public function planPrices()
    {
        return $this->hasMany(PlanPrice::class);
    }
}
