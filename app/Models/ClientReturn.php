<?php

namespace App\Models;

use App\Traits\ScopesByUserRole;
use Illuminate\Database\Eloquent\Model;

class ClientReturn extends Model
{
    use ScopesByUserRole;
    protected $fillable = [
        'client_user_id',
        'return_date',
        'number_of_orders',
        'notes',
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
            'return_date' => 'date',
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
        return $this->belongsToMany(Order::class, 'client_return_orders')
            ->withPivot(['id', 'added_at'])
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
