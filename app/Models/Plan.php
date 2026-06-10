<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'order_count',
    ];

    public function prices()
    {
        return $this->hasMany(PlanPrice::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }
}
