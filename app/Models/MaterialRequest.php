<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\ScopesByUserRole;

class MaterialRequest extends Model
{
    use ScopesByUserRole;
    protected $fillable = [
        'client_id',
        'shipper_id',
        'delivery_type',
        'combined_visit',
        'materials_total',
        'shipping_cost',
        'status',
        'approval_status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'approval_note',
    ];

    protected function casts(): array
    {
        return [
            'combined_visit'  => 'boolean',
            'materials_total' => 'decimal:2',
            'shipping_cost'   => 'decimal:2',
            'approved_at'     => 'datetime',
            'rejected_at'     => 'datetime',
        ];
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function shipper()
    {
        return $this->belongsTo(User::class, 'shipper_id');
    }

    public function items()
    {
        return $this->hasMany(MaterialRequestItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function visit()
    {
        return $this->hasOne(Visit::class);
    }
}
