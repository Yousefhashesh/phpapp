<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\ScopesByUserRole;

use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use ScopesByUserRole, SoftDeletes;


    protected $fillable = [
        'code',
        'external_code',
        'registered_at',
        'captain_date',
        'receiver_name',
        'phone',
        'phone_2',
        'address',
        'governorate_id',
        'city_id',
        'total_amount',
        'shipping_fee',
        'commission_amount',
        'company_amount',
        'cod_amount',
        'status',
        'latest_status_note',
        'order_note',
        'shipper_user_id',
        'client_user_id',
        'shipping_content_id',
        'allow_open',
        'approval_status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'approval_note',
        'is_shipper_collected',
        'shipper_collected_at',
        'is_client_settled',
        'client_settled_at',
        'is_shipper_returned',
        'shipper_returned_at',
        'is_client_returned',
        'has_return',
        'has_return_at',
        'client_returned_at',
        'shipper_date',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'captain_date' => 'date',
            'total_amount' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'company_amount' => 'decimal:2',
            'cod_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'allow_open' => 'boolean',
            'is_shipper_collected' => 'boolean',
            'shipper_collected_at' => 'date',
            'is_client_settled' => 'boolean',
            'client_settled_at' => 'date',
            'is_shipper_returned' => 'boolean',
            'shipper_returned_at' => 'date',
            'is_client_returned' => 'boolean',
            'has_return' => 'boolean',
            'has_return_at' => 'date',
            'client_returned_at' => 'date',
            'shipper_date' => 'date',
        ];
    }

    public function governorate()
    {
        return $this->belongsTo(Governorate::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function shipper()
    {
        return $this->belongsTo(User::class, 'shipper_user_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function shippingContent(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'shipping_content_id');
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

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'entity_id')
            ->where('entity_type', class_basename(self::class));
    }

    public function history(): HasMany
    {
        return $this->activityLogs()->orderByDesc('id');
    }


    public static function generateUniqueCode(): string
    {
        $prefix = Setting::where('key', 'order_prefix')->value('value') ?? 'ORD';
        $digits = (int) (Setting::where('key', 'order_digits')->value('value') ?? 5);

        // Get the last order by ID to determine the next numeric part
        // We use ID instead of code parsing for reliability
        $lastOrder = self::query()->orderByDesc('id')->first();
        $nextNumber = $lastOrder ? ($lastOrder->id + 1) : 1;

        $code = $prefix . '-' . str_pad((string) $nextNumber, $digits, '0', STR_PAD_LEFT);

        // Safety check: ensure the code is actually unique
        while (self::where('code', $code)->exists()) {
            $nextNumber++;
            $code = $prefix . '-' . str_pad((string) $nextNumber, $digits, '0', STR_PAD_LEFT);
        }

        return $code;
    }
    public function refusedReasons()
    {
        return $this->belongsToMany(RefusedReason::class, 'order_refused_reason');
    }

    public function shipperCollections()
    {
        return $this->belongsToMany(ShipperCollection::class, 'shipper_collection_orders');
    }

    public function clientSettlements()
    {
        return $this->belongsToMany(ClientSettlement::class, 'client_settlement_orders');
    }

    public function shipperReturns()
    {
        return $this->belongsToMany(ShipperReturn::class, 'shipper_return_orders');
    }

    public function clientReturns()
    {
        return $this->belongsToMany(ClientReturn::class, 'client_return_orders');
    }

}
