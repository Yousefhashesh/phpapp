<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\ScopesByUserRole;

class Expense extends Model
{
    use SoftDeletes, ScopesByUserRole;

    protected $fillable = [
        'code',
        'category_id',
        'amount',
        'expense_date',
        'title',
        'notes',
        'created_by',
        'approved_by',
        'status',
        'approved_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'expense_date' => 'date',
            'approved_at'  => 'datetime',
            'paid_at'      => 'datetime',
        ];
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
