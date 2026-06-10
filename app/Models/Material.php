<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'name',
        'code',
        'cost_price',
        'sale_price',
        'stock',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_active'  => 'boolean',
        ];
    }

    public function requestItems()
    {
        return $this->hasMany(MaterialRequestItem::class);
    }
}
