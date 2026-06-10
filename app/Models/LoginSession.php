<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'device_name',
        'device_type',
        'browser',
        'platform',
        'country',
        'city',
        'login_at',
        'last_seen_at',
        'logout_at',
        'is_active',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'login_at'      => 'datetime',
            'last_seen_at'  => 'datetime',
            'logout_at'     => 'datetime',
            'is_active'     => 'boolean',
            'is_current'    => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
