<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\ScopesByUserRole;

class Visit extends Model
{
    use ScopesByUserRole;
    protected $fillable = [
        'shipper_id',
        'client_id',
        'pickup_request_id',
        'material_request_id',
        'visit_cost',
    ];

    protected function casts(): array
    {
        return [
            'visit_cost' => 'decimal:2',
        ];
    }

    public function shipper()
    {
        return $this->belongsTo(User::class, 'shipper_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function pickupRequest()
    {
        return $this->belongsTo(PickupRequest::class);
    }

    public function materialRequest()
    {
        return $this->belongsTo(MaterialRequest::class);
    }
}
