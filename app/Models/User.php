<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'username',
        'phone',
        'avatar',
        'password',
        'is_blocked',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_blocked' => 'boolean',
        ];
    }

    public function shipper()
    {
        return $this->hasOne(Shipper::class);
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function loginSessions()
    {
        return $this->hasMany(LoginSession::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? asset('storage/' . $this->avatar) : null;
    }
}
