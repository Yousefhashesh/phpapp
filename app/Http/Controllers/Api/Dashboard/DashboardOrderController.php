<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardOrderController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.dashboard.view');

        // Validation returns the array of validated fields directly
        $validated = $request->validate([
            'client_user_id' => 'nullable|integer',
            'shipper_user_id' => 'nullable|integer',
            'governorate_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'date_field' => 'nullable|string|in:registered_at,created_at,shipper_collected_at,client_settled_at,shipper_returned_at,client_returned_at',
        ]);

        $dateField = $validated['date_field'] ?? 'registered_at';

        // 1. Build Query Base with explicitly scoped table columns
        $query = Order::query()->forUserRole();

        if (! empty($validated['client_user_id'])) {
            $query->where('orders.client_user_id', $validated['client_user_id']);
        }
        if (! empty($validated['shipper_user_id'])) {
            $query->where('orders.shipper_user_id', $validated['shipper_user_id']);
        }
        if (! empty($validated['governorate_id'])) {
            $query->where('orders.governorate_id', $validated['governorate_id']);
        }
        if (! empty($validated['city_id'])) {
            $query->where('orders.city_id', $validated['city_id']);
        }
        if (! empty($validated['start_date'])) {
            $query->where("orders.{$dateField}", '>=', $validated['start_date']);
        }
        if (! empty($validated['end_date'])) {
            $query->where("orders.{$dateField}", '<=', $validated['end_date']);
        }

        // 2. Main Aggregation Execution (Using IFNULL for cleaner dataset results)
        $stats = (clone $query)->selectRaw("
            COUNT(*) as total_orders,
            COUNT(CASE WHEN status = 'OUT_FOR_DELIVERY' THEN 1 END) as out_for_delivery_orders,
            COUNT(CASE WHEN status = 'DELIVERED' THEN 1 END) as delivered_orders,
            COUNT(CASE WHEN status = 'HOLD' THEN 1 END) as hold_orders,
            COUNT(CASE WHEN status = 'UNDELIVERED' THEN 1 END) as undelivered_orders,
            
            COUNT(CASE WHEN approval_status = 'PENDING' THEN 1 END) as pending_approval_orders,
            COUNT(CASE WHEN approval_status = 'APPROVED' THEN 1 END) as approved_orders,
            COUNT(CASE WHEN approval_status = 'REJECTED' THEN 1 END) as rejected_orders,
            
            COUNT(CASE WHEN is_shipper_collected = 1 THEN 1 END) as shipper_collected_orders,
            COUNT(CASE WHEN status IN ('DELIVERED', 'UNDELIVERED') AND is_shipper_collected = 0 THEN 1 END) as uncollected_shipper_orders,
            
            COUNT(CASE WHEN is_client_settled = 1 THEN 1 END) as client_settled_orders,
            COUNT(CASE WHEN status IN ('DELIVERED', 'UNDELIVERED') AND is_shipper_collected = 1 AND is_client_settled = 0 THEN 1 END) as uncollected_client_orders,
            
            COUNT(CASE WHEN is_shipper_returned = 1 THEN 1 END) as shipper_returned_orders,
            COUNT(CASE WHEN is_shipper_returned = 0 AND (status = 'UNDELIVERED' OR (status = 'DELIVERED' AND has_return = 1)) THEN 1 END) as unreturn_shipper_orders,
            
            COUNT(CASE WHEN is_client_returned = 1 THEN 1 END) as client_returned_orders,
            COUNT(CASE WHEN is_shipper_returned = 1 AND is_client_returned = 0 AND (status = 'UNDELIVERED' OR (status = 'DELIVERED' AND has_return = 1)) THEN 1 END) as unreturn_client_orders,
            
            IFNULL(SUM(total_amount), 0) as total_amount_sum,
            IFNULL(SUM(CASE WHEN status = 'OUT_FOR_DELIVERY' THEN total_amount ELSE 0 END), 0) as out_for_delivery_total_sum,
            IFNULL(SUM(CASE WHEN status = 'HOLD' THEN total_amount ELSE 0 END), 0) as hold_total_sum,
            IFNULL(SUM(CASE WHEN status = 'DELIVERED' THEN total_amount ELSE 0 END), 0) as delivered_total_sum,
            IFNULL(SUM(CASE WHEN status = 'UNDELIVERED' THEN total_amount ELSE 0 END), 0) as undelivered_total_sum,

            IFNULL(SUM(shipping_fee), 0) as shipping_fee_sum,
            IFNULL(SUM(commission_amount), 0) as commission_amount_sum,
            IFNULL(SUM(company_amount), 0) as company_amount_sum,
            IFNULL(SUM(cod_amount), 0) as cod_amount_sum,
            
            IFNULL(SUM(CASE WHEN is_shipper_collected = 1 THEN cod_amount ELSE 0 END), 0) as collected_cod_sum,
            IFNULL(SUM(CASE WHEN status IN ('DELIVERED', 'UNDELIVERED') AND is_shipper_collected = 0 THEN cod_amount ELSE 0 END), 0) as uncollected_shipper_cod_sum,
            
            IFNULL(SUM(CASE WHEN is_client_settled = 1 THEN cod_amount ELSE 0 END), 0) as settled_cod_sum,
            IFNULL(SUM(CASE WHEN status IN ('DELIVERED', 'UNDELIVERED') AND is_shipper_collected = 1 AND is_client_settled = 0 THEN cod_amount ELSE 0 END), 0) as uncollected_client_cod_sum,

            IFNULL(SUM(CASE WHEN is_shipper_collected = 1 THEN company_amount ELSE 0 END), 0) as collected_company_net_sum,
            IFNULL(SUM(CASE WHEN status IN ('DELIVERED', 'UNDELIVERED') AND is_shipper_collected = 0 THEN (cod_amount + company_amount) ELSE 0 END), 0) as shipper_net_pending_sum
        ")->first();

        $totalExpenses = (float) Expense::query()
            ->forUserRole()
            ->where('status', 'PAID')
            ->sum('amount');

        // 3. Top Governorates Chart Data (Safe from column ambiguity issues now)
        $topGovernorates = (clone $query)
            ->join('governorates', 'governorates.id', '=', 'orders.governorate_id')
            ->selectRaw('governorates.name as name, COUNT(*) as total_orders')
            ->groupBy('governorates.id', 'governorates.name')
            ->orderByDesc('total_orders')
            ->limit(7)
            ->get()
            ->map(static fn (object $row): array => [
                'name' => (string) ($row->name ?? '-'),
                'total_orders' => (int) ($row->total_orders ?? 0),
            ])
            ->values()
            ->all();

        return response()->json([
            'lifecycle' => [
                'total' => (int) $stats->total_orders,
                'out_for_delivery' => (int) $stats->out_for_delivery_orders,
                'delivered' => (int) $stats->delivered_orders,
                'hold' => (int) $stats->hold_orders,
                'undelivered' => (int) $stats->undelivered_orders,
                'out_for_delivery_amount' => round((float) $stats->out_for_delivery_total_sum, 2),
                'hold_amount' => round((float) $stats->hold_total_sum, 2),
                'delivered_amount' => round((float) $stats->delivered_total_sum, 2),
                'undelivered_amount' => round((float) $stats->undelivered_total_sum, 2),
            ],
            'approval' => [
                'pending' => (int) $stats->pending_approval_orders,
                'approved' => (int) $stats->approved_orders,
                'rejected' => (int) $stats->rejected_orders,
            ],
            'progress' => [
                'shipper_collected' => (int) $stats->shipper_collected_orders,
                'uncollected_shipper' => (int) $stats->uncollected_shipper_orders,
                'client_settled' => (int) $stats->client_settled_orders,
                'uncollected_client' => (int) $stats->uncollected_client_orders,
                'shipper_returned' => (int) $stats->shipper_returned_orders,
                'unreturn_shipper' => (int) $stats->unreturn_shipper_orders,
                'client_returned' => (int) $stats->client_returned_orders,
                'unreturn_client' => (int) $stats->unreturn_client_orders,
            ],
            'financial' => [
                'total_amount' => round((float) $stats->total_amount_sum, 2),
                'shipping_fee' => round((float) $stats->shipping_fee_sum, 2),
                'commission' => round((float) $stats->commission_amount_sum, 2),
                'company_net' => round((float) $stats->company_amount_sum, 2),
                'total_cod' => round((float) $stats->cod_amount_sum, 2),
                'collected_cod' => round((float) $stats->collected_cod_sum, 2),
                'uncollected_shipper_cod' => round((float) $stats->uncollected_shipper_cod_sum, 2),
                'settled_cod' => round((float) $stats->settled_cod_sum, 2),
                'unsettled_client_cod' => round((float) $stats->uncollected_client_cod_sum, 2),
                'collected_company_net' => round((float) $stats->collected_company_net_sum, 2),
                'shipper_net_pending' => round((float) $stats->shipper_net_pending_sum, 2),
                'total_expenses' => round($totalExpenses, 2),
                'total_revenue' => round((float) $stats->collected_company_net_sum - $totalExpenses, 2),
            ],
            'charts' => [
                'top_governorates' => $topGovernorates,
            ],
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }
}