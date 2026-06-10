<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'login_session_id',
        'event_type',
        'entity_type',
        'entity_id',
        'action',
        'label',
        'old_values',
        'new_values',
        'meta',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'meta'       => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function loginSession()
    {
        return $this->belongsTo(LoginSession::class);
    }

    /* --- Accessors for Frontend Compatibility --- */

    public function getActivityAttribute(): string
    {
        return $this->label ?? $this->action ?? 'Unknown Activity';
    }

    public function getDescriptionAttribute(): string
    {
        return $this->action ?? '';
    }

    public function getTypeAttribute(): string
    {
        return $this->event_type ?? 'INFO';
    }

    protected $appends = ['activity', 'description', 'type'];
}
