<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialRequestItem extends Model
{
    protected $fillable = [
        'material_request_id',
        'material_id',
        'quantity',
        'price',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function materialRequest()
    {
        return $this->belongsTo(MaterialRequest::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
