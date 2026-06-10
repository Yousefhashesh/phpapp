<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanPrice extends Model
{
    protected $fillable = [
        'plan_id',
        'governorate_id',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function governorate()
    {
        return $this->belongsTo(Governorate::class);
    }
}
