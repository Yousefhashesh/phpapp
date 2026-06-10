<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function trackOrder(string $code): JsonResponse
    {
        $order = Order::where('code', $code)
            ->orWhere('external_code', $code)
            ->with(['governorate', 'city', 'history' => function($q) {
                $q->orderBy('id', 'desc');
            }])
            ->first();

        if (!$order) {
            return response()->json(['message' => 'الشحنة غير موجودة'], 404);
        }

        return response()->json([
            'code' => $order->code,
            'status' => $order->status,
            'receiver_name' => $order->receiver_name,
            'governorate' => $order->governorate?->name,
            'city' => $order->city?->name,
            'registered_at' => $order->registered_at?->format('Y-m-d H:i'),
            'history' => $order->history->map(fn($log) => [
                'activity' => $log->activity,
                'description' => $log->description,
                'created_at' => $log->created_at?->format('Y-m-d H:i'),
            ]),
        ]);
    }

    public function index(): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key');
        $allDefaults = Setting::getDefaults();
        
        $siteSettings = [
            'name' => $settings->get('site_name', $allDefaults['site_name'] ?? 'Shipya'),
            'logo' => $settings->get('site_logo_512_light', $allDefaults['site_logo_512_light'] ?? ''),
            'email' => $settings->get('site_email', $allDefaults['site_email'] ?? ''),
            'phone' => $settings->get('site_phone', $allDefaults['site_phone'] ?? ''),
            'address' => $settings->get('site_address', $allDefaults['site_address'] ?? ''),
        ];

        // Fetch plans based on 'welcome_plans' setting
        $welcomePlanIds = $settings->get('welcome_plans', 'all');
        $query = Plan::with('prices.governorate');
        
        if ($welcomePlanIds !== 'all') {
            $ids = explode(',', $welcomePlanIds);
            $query->whereIn('id', $ids);
        }

        $plans = $query->get()->map(function ($plan) {
            // Group prices by amount
            $groupedPrices = $plan->prices->groupBy(fn($p) => (string)round($p->price, 0))
                ->map(function ($items, $price) {
                    return [
                        'price' => (float)$price,
                        'governorates' => $items->pluck('governorate.name')->toArray(),
                    ];
                })->values();

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'order_count' => $plan->order_count,
                'grouped_prices' => $groupedPrices,
            ];
        });

        return response()->json([
            'site' => $siteSettings,
            'plans' => $plans,
        ]);
    }
}
