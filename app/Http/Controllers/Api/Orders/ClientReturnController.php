<?php

namespace App\Http\Controllers\Api\Orders;

use App\Exports\ReturnedClientsExport;
use App\Http\Controllers\Controller;
use App\Models\ClientReturn;
use App\Models\ClientReturnOrder;
use App\Models\Order;
use App\Support\Permissions\CollectionsReturnsSettlementsPermissionMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ClientReturnController extends Controller
{
    public const ELIGIBLE_ORDER_STATUSES = ['DELIVERED', 'UNDELIVERED'];

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.page');
        $this->authorizePermission($request, 'client-return.view');

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => [Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
            'client_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'approval_status' => ['nullable', Rule::in(['PENDING', 'APPROVED', 'REJECTED'])],
            'search' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $statuses = [];
        if (! empty($validated['status'])) {
            $statuses[] = $validated['status'];
        }

        if (is_array($validated['statuses'] ?? null)) {
            $statuses = array_values(array_unique([
                ...$statuses,
                ...$validated['statuses'],
            ]));
        }

        $returns = ClientReturn::query()
            ->forUserRole()
            ->with(['client:id,name'])
            ->when(
                $statuses !== [],
                fn (Builder $query): Builder => $query->whereIn('status', $statuses)
            )
            ->when(
                $validated['approval_status'] ?? null,
                fn (Builder $query, string $approvalStatus): Builder => $query->where('approval_status', $approvalStatus)
            )
            ->when(
                $validated['client_user_id'] ?? null,
                fn (Builder $query, int $clientUserId): Builder => $query->where('client_user_id', $clientUserId)
            )
            ->when(
                $validated['q'] ?? $validated['search'] ?? null,
                function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search): void {
                        $q->where('id', 'like', "%{$search}%")
                            ->orWhereHas('client', fn ($sq) => $sq->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('orders', fn ($sq) => $sq
                                ->where('code', 'like', "%{$search}%")
                                ->orWhere('external_code', 'like', "%{$search}%"));
                    });
                }
            )
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $returns->map(fn (ClientReturn $return): array => $this->filterVisibleColumns($request, $return))->values()
        );
    }

    public function export(Request $request)
    {
        $this->authorizePermission($request, 'client-return.export');

        $ids = $request->input('ids');
        if ($ids && is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $query = ClientReturn::query();
        if ($ids && is_array($ids)) {
            $query->whereIn('id', $ids);
        }

        $totalOrders = $query->sum('number_of_orders');

        $clients = $query->with('client')->get()->pluck('client.name')->unique();
        $namePart = ($clients->count() === 1) ? " - " . $clients->first() : "";

        $date = now()->format('d-m-y');
        $filename = "مرتجع عميل{$namePart} - {$totalOrders} - {$date}.xlsx";

        return Excel::download(new ReturnedClientsExport($ids ? null : $query, $ids ? $ids : null), $filename);
    }

    public function bulkStatus(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.update');

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:client_returns,id'],
            'status' => ['required', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
        ]);

        DB::transaction(function () use ($data) {
            $returns = ClientReturn::whereIn('id', $data['ids'])->get();
            foreach ($returns as $return) {
                $return->update(['status' => $data['status']]);
                $this->syncReturnOrdersState($return);
            }
        });

        return response()->json(['message' => 'Status updated for selected returns.']);
    }

    public function eligibleOrders(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.page');
        $this->authorizePermission($request, 'client-return.view');

        $validated = $request->validate([
            'client_user_id' => ['nullable', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 100);

        $orders = Order::query()
            ->forUserRole()
            ->select([
                'id',
                'code',
                'external_code',
                'receiver_name',
                'phone',
                'phone_2',
                'address',
                'total_amount',
                'shipping_fee',
                'commission_amount',
                'company_amount',
                'cod_amount',
                'status',
                'client_user_id',
                'shipper_user_id',
                'is_shipper_returned',
                'shipper_returned_at',
                'is_client_returned',
                'client_returned_at',
            ])
            ->with(['client:id,name,phone', 'shipper:id,name'])
            ->whereIn('status', self::ELIGIBLE_ORDER_STATUSES)
            ->where('is_shipper_returned', true)
            ->whereDoesntHave('clientReturns', function ($q) {
                $q->where('client_returns.status', '!=', 'CANCELLED');
            })
            ->where('is_client_returned', false)
            ->when(
                $validated['client_user_id'] ?? null,
                fn (Builder $query, int|string $clientUserId): Builder => $query->where('client_user_id', $clientUserId)
            )
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'data' => collect($orders->items())
                ->map(fn (Order $order): array => $this->formatEligibleOrder($order))
                ->values(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
                'eligible_statuses' => self::ELIGIBLE_ORDER_STATUSES,
                'requires_shipper_return_first' => true,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.create');

        $data = $request->validate([
            'client_user_id' => ['required', 'exists:users,id'],
            'return_date' => ['required', 'date'],
            'number_of_orders' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'order_ids' => ['nullable', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'distinct', 'exists:orders,id'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        $orderIds = array_values(array_unique($data['order_ids'] ?? []));
        unset($data['order_ids']);

        $creatorId = $request->user()->id;
        $canApproveOnCreate = $request->user()?->can('client-return.approve') ?? false;

        $data['created_by'] = $creatorId;
        $data['approval_status'] = $canApproveOnCreate ? 'APPROVED' : 'PENDING';
        $data['approved_by'] = $canApproveOnCreate ? $creatorId : null;
        $data['approved_at'] = $canApproveOnCreate ? now() : null;
        $data['rejected_by'] = null;
        $data['rejected_at'] = null;
        $data['approval_note'] = null;

        $return = DB::transaction(function () use ($data, $orderIds): ClientReturn {
            $return = ClientReturn::query()->create($data);

            if ($orderIds !== []) {
                $orders = $this->resolveEligibleReturnOrders($data['client_user_id'], $orderIds);
                $this->attachOrdersToReturn($return, $orders);
                $this->syncReturnOrdersState($return, $orders->pluck('id')->all());
            }

            return $return;
        });

        return response()->json([
            'message' => 'Client return created successfully.',
            'data' => $this->filterVisibleColumns($request, $return),
        ], 201);
    }

    public function show(Request $request, ClientReturn $clientReturn): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.page');
        $this->authorizePermission($request, 'client-return.view');

        return response()->json($this->returnDetailsPayload($request, $clientReturn));
    }

    public function update(Request $request, ClientReturn $clientReturn): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.update');

        $data = $request->validate([
            'client_user_id' => ['sometimes', 'required', 'exists:users,id'],
            'return_date' => ['sometimes', 'required', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));
        $this->ensureReturnCanBeCompleted($clientReturn, $data['status'] ?? null);
        $this->authorizeUnlockReturn($request, $clientReturn, $data['status'] ?? null);

        DB::transaction(function () use ($clientReturn, $data): void {
            $clientReturn->update($data);
            $this->syncReturnOrdersState($clientReturn);
        });

        return response()->json([
            'message' => 'Client return updated successfully.',
            'data' => $this->filterVisibleColumns($request, $clientReturn),
        ]);
    }

    public function destroy(Request $request, ClientReturn $clientReturn): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.delete');
        $this->authorizeUnlockReturn($request, $clientReturn, 'DELETE');

        DB::transaction(function () use ($clientReturn): void {
            $this->syncReturnOrdersState($clientReturn, null, true);
            $clientReturn->delete();
        });

        return response()->json([
            'message' => 'Client return deleted successfully.',
        ]);
    }

    public function approve(Request $request, ClientReturn $clientReturn): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.approve');

        $data = $request->validate([
            'approval_note' => ['nullable', 'string'],
        ]);

        $clientReturn->update([
            'approval_status' => 'APPROVED',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'approval_note' => $data['approval_note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Client return approved successfully.',
            'data' => $this->filterVisibleColumns($request, $clientReturn->fresh()),
        ]);
    }

    public function reject(Request $request, ClientReturn $clientReturn): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.reject');

        $data = $request->validate([
            'approval_note' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $clientReturn, $data): void {
            $clientReturn->update([
                'approval_status' => 'REJECTED',
                'rejected_by' => $request->user()->id,
                'rejected_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'approval_note' => $data['approval_note'] ?? null,
                'status' => 'CANCELLED',
            ]);

            $this->syncReturnOrdersState($clientReturn);
        });

        return response()->json([
            'message' => 'Client return rejected successfully.',
            'data' => $this->filterVisibleColumns($request, $clientReturn->fresh()),
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function authorizeEditableColumns(Request $request, array $columns): void
    {
        foreach ($columns as $column) {
            $permission = CollectionsReturnsSettlementsPermissionMap::CLIENT_RETURN_EDIT_COLUMNS[$column] ?? null;

            if ($permission && ! $request->user()?->can($permission)) {
                abort(403, "Missing permission: {$permission}");
            }
        }
    }

    private function ensureReturnCanBeCompleted(ClientReturn $return, ?string $nextStatus): void
    {
        if ($nextStatus !== 'COMPLETED') {
            return;
        }

        if ($return->approval_status !== 'APPROVED') {
            throw ValidationException::withMessages([
                'status' => ['Client return must be approved before it can be completed.'],
            ]);
        }
    }

    private function authorizeUnlockReturn(Request $request, ClientReturn $return, ?string $nextStatus): void
    {
        if (! $this->willUnlockReturn($return, $nextStatus)) {
            return;
        }

        $this->authorizePermission($request, 'client-return.unlock');
    }

    private function willUnlockReturn(ClientReturn $return, ?string $nextStatus): bool
    {
        if ($nextStatus === 'DELETE') {
            return $return->orders()->exists();
        }

        if ($nextStatus === null || $nextStatus === $return->status) {
            return false;
        }

        if ($nextStatus === 'CANCELLED') {
            return true;
        }

        return $return->status === 'COMPLETED' && $nextStatus !== 'COMPLETED';
    }

    private function filterVisibleColumns(Request $request, ClientReturn $return): array
    {
        $payload = $return->toArray();
        $result = [];

        foreach (CollectionsReturnsSettlementsPermissionMap::CLIENT_RETURN_VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $payload[$column];
            }
        }

        if (! array_key_exists('id', $result)) {
            $result['id'] = $return->id;
        }

        return $result;
    }

    private function formatEligibleOrder(Order $order): array
    {
        $shipperReturnedAt = $order->shipper_returned_at;
        $clientReturnedAt = $order->client_returned_at;

        return [
            'id' => $order->id,
            'code' => $order->code,
            'external_code' => $order->external_code,
            'receiver_name' => $order->receiver_name,
            'phone' => $order->phone,
            'phone_2' => $order->phone_2,
            'address' => $order->address,
            'total_amount' => $order->total_amount,
            'shipping_fee' => $order->shipping_fee,
            'commission_amount' => $order->commission_amount,
            'company_amount' => $order->company_amount,
            'cod_amount' => $order->cod_amount,
            'status' => $order->status,
            'client_user_id' => $order->client_user_id,
            'client_name' => $order->client?->name,
            'client' => [
                'name' => $order->client?->name,
                'phone' => $order->client?->phone,
            ],
            'shipper_user_id' => $order->shipper_user_id,
            'shipper_name' => $order->shipper?->name,
            'is_shipper_returned' => (bool) $order->is_shipper_returned,
            'shipper_returned_at' => $shipperReturnedAt instanceof \DateTimeInterface ? $shipperReturnedAt->format('Y-m-d') : ($shipperReturnedAt !== null ? (string) $shipperReturnedAt : null),
            'is_client_returned' => (bool) $order->is_client_returned,
            'client_returned_at' => $clientReturnedAt instanceof \DateTimeInterface ? $clientReturnedAt->format('Y-m-d') : ($clientReturnedAt !== null ? (string) $clientReturnedAt : null),
        ];
    }

    private function resolveEligibleReturnOrders(int $clientUserId, array $orderIds): Collection
    {
        $orders = Order::query()
            ->forUserRole()
            ->select([
                'id',
                'client_user_id',
                'status',
                'is_shipper_returned',
                'is_client_returned',
            ])
            ->where('client_user_id', $clientUserId)
            ->whereIn('id', $orderIds)
            ->whereIn('status', self::ELIGIBLE_ORDER_STATUSES)
            ->where('is_shipper_returned', true)
            ->whereDoesntHave('clientReturns', function ($q) {
                $q->where('client_returns.status', '!=', 'CANCELLED');
            })
            ->where('is_client_returned', false)
            ->get();

        if ($orders->count() !== count($orderIds)) {
            throw ValidationException::withMessages([
                'order_ids' => ['One or more selected orders are not eligible for client return.'],
            ]);
        }

        return $orders;
    }

    private function attachOrdersToReturn(ClientReturn $return, Collection $orders): void
    {
        $timestamp = now();
        $rows = $orders
            ->map(fn (Order $order): array => [
                'client_return_id' => $return->id,
                'order_id' => $order->id,
                'added_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->all();

        if ($rows !== []) {
            ClientReturnOrder::query()->insert($rows);
        }
    }

    private function syncReturnOrdersState(ClientReturn $return, ?array $orderIds = null, bool $reset = false): void
    {
        $orderIds ??= $return->orders()->pluck('orders.id')->all();

        if ($orderIds === []) {
            return;
        }

        if ($reset || $return->status === 'CANCELLED') {
            Order::query()
                ->whereIn('id', $orderIds)
                ->update([
                    'is_client_returned' => false,
                    'client_returned_at' => null,
                ]);

            return;
        }

        Order::query()
            ->whereIn('id', $orderIds)
            ->update([
                'is_client_returned' => $return->status === 'COMPLETED',
                'client_returned_at' => $return->status === 'COMPLETED' ? $return->return_date : null,
            ]);
    }

    public function removeOrder(Request $request, ClientReturn $clientReturn, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.update');

        DB::transaction(function () use ($clientReturn, $order) {
            $pivot = ClientReturnOrder::where('client_return_id', $clientReturn->id)
                ->where('order_id', $order->id)
                ->first();

            if ($pivot) {
                // Update return totals
                $clientReturn->number_of_orders = max(0, $clientReturn->number_of_orders - 1);
                
                if ($clientReturn->number_of_orders <= 0) {
                    $clientReturn->delete();
                } else {
                    $clientReturn->save();
                }

                // Delete pivot record
                $pivot->delete();

                // Reset order state
                $order->update([
                    'is_client_returned' => false,
                    'client_returned_at' => null,
                ]);
            }
        });

        if (!ClientReturn::where('id', $clientReturn->id)->exists()) {
            return response()->json([
                'message' => 'Return deleted because it had no more orders.',
                'deleted' => true
            ]);
        }

        return response()->json([
            'message' => 'Order removed from return.',
            'data' => $this->returnDetailsPayload($request, $clientReturn->fresh()),
        ]);
    }

    private function loadReturnOrderRelations(ClientReturn $clientReturn): ClientReturn
    {
        return $clientReturn->load([
            'client:id,name,phone',
            'orders' => fn ($query) => $query->select([
                'orders.id',
                'orders.code',
                'orders.external_code',
                'orders.receiver_name',
                'orders.phone',
                'orders.phone_2',
                'orders.status',
                'orders.client_user_id',
                'orders.shipper_user_id',
                'orders.total_amount',
                'orders.shipping_fee',
                'orders.commission_amount',
                'orders.company_amount',
                'orders.cod_amount',
            ]),
            'orders.client:id,name,phone',
            'orders.shipper:id,name,phone',
        ]);
    }

    private function returnDetailsPayload(Request $request, ClientReturn $clientReturn): array
    {
        $this->loadReturnOrderRelations($clientReturn);

        $result = $this->filterVisibleColumns($request, $clientReturn);
        $result['orders'] = $clientReturn->orders;

        return $result;
    }

    public function bulkScan(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.create');

        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['exists:orders,id'],
            'return_date' => ['nullable', 'date'],
        ]);

        $orderIds = $data['order_ids'];
        $returnDate = $data['return_date'] ?? now()->toDateString();
        $creatorId = $request->user()->id;

        // Fetch eligible orders
        $orders = Order::query()
            ->whereIn('id', $orderIds)
            ->whereIn('status', self::ELIGIBLE_ORDER_STATUSES)
            // Client return needs has_return=true AND already returned by shipper
            ->where('has_return', true)
            ->where('is_shipper_returned', true)
            ->whereDoesntHave('clientReturns', function ($q) {
                $q->where('client_returns.status', '!=', 'CANCELLED');
            })
            ->where('is_client_returned', false)
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No eligible orders found for client return.'], 422);
        }

        $processedIds = [];
        DB::transaction(function () use ($orders, $returnDate, $creatorId, &$processedIds) {
            // Group by client
            foreach ($orders->groupBy('client_user_id') as $clientId => $clientOrders) {
                if (!$clientId) continue;

                $return = ClientReturn::create([
                    'client_user_id' => $clientId,
                    'return_date' => $returnDate,
                    'number_of_orders' => $clientOrders->count(),
                    'status' => 'COMPLETED',
                    'approval_status' => 'APPROVED',
                    'created_by' => $creatorId,
                    'approved_by' => $creatorId,
                    'approved_at' => now(),
                ]);

                $this->attachOrdersToReturn($return, $clientOrders);
                $this->syncReturnOrdersState($return, $clientOrders->pluck('id')->all());
                
                $processedIds = array_merge($processedIds, $clientOrders->pluck('id')->all());
            }
        });

        return response()->json([
            'message' => 'Processed ' . count($processedIds) . ' orders into client returns.',
            'processed_ids' => $processedIds
        ]);
    }
}
