<?php

namespace App\Models;

use App\Traits\ScopesByUserRole;
use Illuminate\Database\Eloquent\Model;

class ShipperCollection extends Model
{
    use ScopesByUserRole;
    protected $fillable = [
        'shipper_user_id',
        'collection_date',
        'total_amount',
        'number_of_orders',
        'shipper_fees',
        'net_amount',
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
            'collection_date' => 'date',
            'total_amount'    => 'decimal:2',
            'shipper_fees'    => 'decimal:2',
            'net_amount'      => 'decimal:2',
            'approved_at'     => 'datetime',
            'rejected_at'     => 'datetime',
        ];
    }

    protected $appends = ['fees'];

    public function getFeesAttribute()
    {
        return round(($this->total_amount ?? 0) - ($this->net_amount ?? 0), 2);
    }

    public function shipper()
    {
        return $this->belongsTo(User::class, 'shipper_user_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'shipper_collection_orders')
            ->withPivot(['id', 'order_amount', 'shipper_fee', 'net_amount', 'added_at'])
            ->withTimestamps();
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
}
