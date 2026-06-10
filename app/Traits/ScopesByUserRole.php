<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait ScopesByUserRole
{
    /**
     * Scope a query to only include records relevant to the current user's role.
     */
    public function scopeForUserRole(Builder $query): Builder
    {
        $user = Auth::user();
        if (!$user) {
            return $query->whereRaw('1=0'); // Don't show anything if not logged in
        }

        // Admin sees everything - Case-insensitive check
        $userRoles = $user->roles->pluck('name')->map(fn($n) => strtolower($n))->toArray();
        if (in_array('admin', $userRoles) || in_array('super-admin', $userRoles)) {
            return $query;
        }

        $tableName = $this->getTable();

        // Shipper Scope
        if ($user->hasRole('shipper')) {
            if ($tableName === 'orders') {
                return $query->where('shipper_user_id', $user->id);
            }
            if ($tableName === 'shipper_collections') {
                return $query->where('shipper_user_id', $user->id);
            }
             if ($tableName === 'shipper_returns') {
                return $query->where('shipper_user_id', $user->id);
            }
            if ($tableName === 'material_requests') {
                return $query->where('shipper_user_id', $user->id);
            }
             if ($tableName === 'visits') {
                return $query->where('shipper_user_id', $user->id);
            }
            if ($tableName === 'expenses') {
                return $query->where('created_by', $user->id);
            }
            // Shippers usually don't see other data
            return $query;
        }

        // Client Scope
        if ($user->hasRole('client')) {
            if ($tableName === 'orders') {
                return $query->where('client_user_id', $user->id);
            }
             if ($tableName === 'client_settlements') {
                return $query->where('client_user_id', $user->id);
            }
             if ($tableName === 'client_returns') {
                return $query->where('client_user_id', $user->id);
            }
             if ($tableName === 'pickup_requests') {
                return $query->where('client_user_id', $user->id);
            }
             if ($tableName === 'visits') {
                return $query->where('client_user_id', $user->id);
            }
            if ($tableName === 'expenses') {
                 // Maybe clients can't see any expenses or they only see their own?
                 // In this system, expenses are likely for the company.
                 return $query->whereRaw('1=0');
            }
            return $query;
        }

        // Default or other roles
        return $query;
    }
}
