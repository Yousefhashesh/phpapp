<?php

namespace App\Providers;

use App\Models\ClientReturn;
use App\Models\ClientSettlement;
use App\Models\Content;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Governorate;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\Order;
use App\Models\PickupRequest;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\RefusedReason;
use App\Models\Role;
use App\Models\Setting;
use App\Models\ShipperCollection;
use App\Models\ShipperReturn;
use App\Models\User;
use App\Models\Visit;
use App\Observers\EntityObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(static function (User $user, string $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        // Register entity observer for activity logging
        Expense::observe(EntityObserver::class);
        ExpenseCategory::observe(EntityObserver::class);
        User::observe(EntityObserver::class);
        Content::observe(EntityObserver::class);
        Setting::observe(EntityObserver::class);
        Governorate::observe(EntityObserver::class);
        Plan::observe(EntityObserver::class);
        PlanPrice::observe(EntityObserver::class);
        Material::observe(EntityObserver::class);
        MaterialRequest::observe(EntityObserver::class);
        MaterialRequestItem::observe(EntityObserver::class);
        PickupRequest::observe(EntityObserver::class);
        Visit::observe(EntityObserver::class);
        RefusedReason::observe(EntityObserver::class);
        Order::observe(EntityObserver::class);
        ShipperCollection::observe(EntityObserver::class);
        ShipperReturn::observe(EntityObserver::class);
        ClientSettlement::observe(EntityObserver::class);
        ClientReturn::observe(EntityObserver::class);
        Role::observe(EntityObserver::class);
    }
}
