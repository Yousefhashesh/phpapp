<?php

namespace App\Models;

use App\Traits\ScopesByUserRole;
use Illuminate\Database\Eloquent\Model;

class ClientSettlement extends Model
{
    use ScopesByUserRole;

    protected $fillable = [
        'client_user_id',
        'settlement_date',
        'total_amount',
        'number_of_orders',
        'fees',
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
            'settlement_date' => 'date',
            'total_amount' => 'decimal:2',
            'fees' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'client_settlement_orders')
            ->withPivot(['id', 'order_amount', 'fee', 'net_amount', 'added_at'])
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
