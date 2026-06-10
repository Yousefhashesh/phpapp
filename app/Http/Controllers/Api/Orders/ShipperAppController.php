<?php

namespace App\Http\Controllers\Api\Orders;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RefusedReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShipperAppController extends Controller
{
    /**
     * Get orders assigned to the current shipper.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.my-orders');
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'status' => ['nullable', Rule::in(['OUT_FOR_DELIVERY', 'DELIVERED', 'HOLD', 'UNDELIVERED'])],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $perPage = $validated['per_page'] ?? 20;

        $orders = Order::query()
            ->forUserRole()
            ->when($validated['status'] ?? null, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($validated['q'] ?? null, function ($query, $search) {
                $anyLike = '%' . $search . '%';
                return $query->where(function ($q) use ($anyLike) {
                    $q->where('code', 'like', $anyLike)
                      ->orWhere('external_code', 'like', $anyLike)
                      ->orWhere('total_amount', 'like', $anyLike)
                      ->orWhere('receiver_name', 'like', $anyLike)
                      ->orWhere('phone', 'like', $anyLike)
                      ->orWhere('phone_2', 'like', $anyLike);
                });
            })
            ->with(['governorate:id,name', 'city:id,name', 'shippingContent:id,name'])
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Get details of a specific assigned order.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.view');
        if ($order->shipper_user_id !== $request->user()->id && !$request->user()->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'This order is not assigned to you.');
        }

        $order->load(['governorate:id,name', 'city:id,name', 'shippingContent:id,name', 'client:id,name']);

        return response()->json($order);
    }

    /**
     * Update order status (Shipper specific simplified logic).
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.change-status');
        if ($order->shipper_user_id !== $request->user()->id && !$request->user()->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'This order is not assigned to you.');
        }

        if ($order->is_shipper_collected) {
            throw ValidationException::withMessages([
                'status' => ['Cannot change status. This order has already been collected in a settlement.'],
            ]);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['DELIVERED', 'HOLD', 'UNDELIVERED'])],
            'reason_id' => ['nullable', 'exists:refused_reasons,id'],
            'note' => ['nullable', 'string', 'max:500'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $status = $validated['status'];
        $reasonId = $validated['reason_id'] ?? null;
        $note = $validated['note'] ?? null;
        $totalAmount = $validated['total_amount'] ?? null;

        // Validation for reasons
        if (in_array($status, ['HOLD', 'UNDELIVERED']) && !$reasonId && !$note) {
            throw ValidationException::withMessages([
                'reason_id' => ['A reason or note is required for HOLD or UNDELIVERED status.'],
            ]);
        }

        DB::transaction(function () use ($order, $status, $reasonId, $note, $totalAmount) {
            $updateData = ['status' => $status];
            
            if ($reasonId) {
                $reason = RefusedReason::find($reasonId);
                if ($reason) {
                    $updateData['latest_status_note'] = $reason->reason . ($note ? " - " . $note : "");

                    // Handle Partial Delivery (Edit Amount)
                    if ($reason->is_edit_amount) {
                        if ($totalAmount !== null) {
                            $updateData['total_amount'] = $totalAmount;
                        }

                        if ($status === 'DELIVERED') {
                            $updateData['has_return'] = true;
                            $updateData['has_return_at'] = now();
                        }
                    }

                    // Handle Clearing
                    if ($reason->is_clear) {
                        $updateData['total_amount'] = 0;
                        $updateData['shipping_fee'] = 0;
                        $updateData['commission_amount'] = 0;
                        $updateData['company_amount'] = 0;
                        $updateData['cod_amount'] = 0;
                    }

                    // If amount was edited, re-apply automatic calculations
                    if (isset($updateData['total_amount']) && !$reason->is_clear) {
                        // We use the same service logic or manual re-calc
                        $updateData = $this->recalcFinancialsForOrder($updateData, $order);
                    }
                }
            } elseif ($note) {
                $updateData['latest_status_note'] = $note;
            }

            $order->update($updateData);

            if ($reasonId) {
                $order->refusedReasons()->syncWithoutDetaching([$reasonId]);
            }
        });

        return response()->json([
            'message' => 'Order status updated successfully.',
            'order' => $order->fresh(),
        ]);
    }

    /**
     * Quick scan/search for an order assigned to the shipper.
     */
    public function scan(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.view');
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $order = Order::query()
            ->forUserRole()
            ->where(function($q) use ($request) {
                $q->where('code', $request->code)
                  ->orWhere('external_code', $request->code);
            })
            ->with(['governorate:id,name', 'city:id,name'])
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found in your assigned list.'], 404);
        }

        return response()->json($order);
    }

    /**
     * Statistics for the representative app.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.view');
        $query = Order::query()->forUserRole();

        $stats = [
            'total_assigned' => $query->clone()->count(),
            'out_for_delivery' => $query->clone()->where('status', 'OUT_FOR_DELIVERY')->count(),
            'delivered_today' => $query->clone()
                ->where('status', 'DELIVERED')
                ->whereDate('updated_at', now())
                ->count(),
            'pending_collection' => $query->clone()
                ->where('status', 'DELIVERED')
                ->where('is_shipper_collected', false)
                ->whereDoesntHave('shipperCollections', function ($q) {
                    $q->where('shipper_collections.status', '!=', 'CANCELLED');
                })
                ->sum('cod_amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Get collections related to the current shipper.
     */
    public function collections(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.view');
        
        $collections = \App\Models\ShipperCollection::query()
            ->where('shipper_user_id', $request->user()->id)
            ->withCount('orders')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 20));

        return response()->json($collections);
    }

    /**
     * Get details of a specific collection.
     */
    public function collectionDetails(Request $request, \App\Models\ShipperCollection $collection): JsonResponse
    {
        $this->authorizePermission($request, 'order.view');
        
        if ($collection->shipper_user_id !== $request->user()->id && !$request->user()->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'This collection is not yours.');
        }

        $collection->load(['orders.client:id,name', 'orders.governorate:id,name', 'orders.city:id,name']);

        return response()->json($collection);
    }

    /**
     * Get orders that are delivered but not yet collected.
     */
    public function pendingOrders(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.view');

        $orders = Order::query()
            ->where('shipper_user_id', $request->user()->id)
            ->where('status', 'DELIVERED')
            ->where('is_shipper_collected', false)
            ->whereDoesntHave('shipperCollections', function ($q) {
                $q->where('shipper_collections.status', '!=', 'CANCELLED');
            })
            ->with(['governorate:id,name', 'city:id,name', 'client:id,name'])
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 20));

        return response()->json($orders);
    }

    /**
     * Initial data for the app (reasons).
     */
    public function init(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.view');
        return response()->json([
            'refused_reasons' => RefusedReason::where('is_active', true)->get(),
        ]);
    }

    private function recalcFinancialsForOrder(array $data, Order $order): array
    {
        $clientUserId = $order->client_user_id;
        $governorateId = $order->governorate_id;
        $shipperUserId = $order->shipper_user_id;
        $totalAmount = $data['total_amount'] ?? $order->total_amount;

        $shippingFee = $this->resolveShippingFee((int) $clientUserId, (int) $governorateId);
        $commissionAmount = $this->resolveCommissionAmount($shipperUserId ? (int) $shipperUserId : null);

        if ($shippingFee === null && $shipperUserId !== null) {
            $shippingFee = $commissionAmount;
        }

        $total = round((float) $totalAmount, 2);

        $data['total_amount'] = $total;
        $data['shipping_fee'] = round((float) ($shippingFee ?? 0), 2);
        $data['commission_amount'] = round((float) ($commissionAmount ?? 0), 2);
        $data['company_amount'] = round($data['shipping_fee'] - $data['commission_amount'], 2);
        $data['cod_amount'] = round($total - $data['shipping_fee'], 2);

        return $data;
    }

    private function resolveShippingFee(int $clientUserId, int $governorateId): ?float
    {
        $client = \App\Models\User::find($clientUserId);
        if (!$client || !$client->clientPlan) {
            return null;
        }

        $fee = $client->clientPlan->governorates()
            ->where('governorate_id', $governorateId)
            ->first()?->pivot->fee;

        return $fee !== null ? (float) $fee : null;
    }

    private function resolveCommissionAmount(?int $shipperUserId): float
    {
        if ($shipperUserId === null) {
            return 0;
        }

        $shipper = \App\Models\User::find($shipperUserId);
        if (!$shipper || $shipper->shipper_commission_fee === null) {
            return 0;
        }

        return (float) $shipper->shipper_commission_fee;
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }
}
