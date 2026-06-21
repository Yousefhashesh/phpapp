<?php

namespace App\Http\Controllers\Api\Orders;

use App\Exports\CollectedShippersExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShipperCollection;
use App\Models\ShipperCollectionOrder;
use App\Support\Permissions\CollectionsReturnsSettlementsPermissionMap;
use App\Support\Services\FinancialFormulaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

use App\Traits\ChecksWorkingHours;

class ShipperCollectionController extends Controller
{
    use ChecksWorkingHours;
    public const ELIGIBLE_ORDER_STATUSES = ['DELIVERED', 'UNDELIVERED'];

//     public function index(Request $request): JsonResponse
//     {
//         $this->authorizePermission($request, 'shipper-collection.page');
//         $this->authorizePermission($request, 'shipper-collection.view');

//         $validated = $request->validate([
//             'status' => ['nullable', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
//             'statuses' => ['nullable', 'array'],
//             'statuses.*' => [Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
//             'approval_status' => ['nullable', Rule::in(['PENDING', 'APPROVED', 'REJECTED'])],
//             'shipper_user_id' => ['nullable', 'exists:users,id'],
//             'search' => ['nullable', 'string'],
//         ]);

//         $statuses = [];
//         if (! empty($validated['status'])) {
//             $statuses[] = $validated['status'];
//         }

//         if (is_array($validated['statuses'] ?? null)) {
//             $statuses = array_values(array_unique([
//                 ...$statuses,
//                 ...$validated['statuses'],
//             ]));
//         }

//         $precomputedPermissions = $this->precomputeVisibleColumns($request);

//         $collections = ShipperCollection::query()
//             ->forUserRole()
//             ->select('shipper_collections.*')
//             ->with(['shipper:id,name'])
//             ->withCount('orders')
//             ->withSum('orders as total_order_fees', 'shipping_fee')
//             ->selectSub(
//                 ShipperCollectionOrder::query()
//                     ->selectRaw('COALESCE(SUM(shipper_fee), 0)')
//                     ->whereColumn('shipper_collection_id', 'shipper_collections.id'),
//                 'pivot_shipper_fees'
//             )
//             ->when(
//                 $statuses !== [],
//                 fn (Builder $query): Builder => $query->whereIn('status', $statuses)
//             )
//             ->when(
//                 $validated['approval_status'] ?? null,
//                 fn (Builder $query, string $status): Builder => $query->where('approval_status', $status)
//             )
//             ->when(
//                 $validated['shipper_user_id'] ?? null,
//                 fn (Builder $query, int|string $id): Builder => $query->where('shipper_user_id', $id)
//             )
//             ->when(
//                 $validated['q'] ?? $validated['search'] ?? null,
//                 function (Builder $query, string $search): Builder {
//                     return $query->where(function (Builder $builder) use ($search): void {
//                         $builder->where('id', 'like', "%{$search}%")
//                             ->orWhereHas('shipper', fn ($q) => $q->where('name', 'like', "%{$search}%"))
//                             ->orWhereHas('orders', fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('external_code', 'like', "%{$search}%"));
//                     });
//                 }
//             )
//             ->orderByDesc('id')
//             ->paginate($request->input('per_page', 100))
//             ->appends($request->query());

      

// return response()->json([
//     'data' => collect($collections->items())->map(function (ShipperCollection $collection) use ($request, $precomputedPermissions): array {
      
//         $filteredData = $this->filterVisibleColumns($request, $collection, $precomputedPermissions);

//         $filteredData['shipping_fee'] = (float) ($collection->total_order_fees ?? 0);

//         if (array_key_exists('shipper_fees', $filteredData)) {
//             $filteredData['shipper_fees'] = $this->resolveShipperFees($collection);
//         }

//         if (array_key_exists('fees', $filteredData)) {
//             $filteredData['fees'] = $filteredData['shipper_fees'] ?? $this->resolveShipperFees($collection);
//         }

//         return $filteredData;
//     })->values(),
//     'meta' => [
//         'current_page' => $collections->currentPage(),
//         'per_page' => $collections->perPage(),
//         'last_page' => $collections->lastPage(),
//         'total' => $collections->total(),
//     ],
// ]);
//     }


public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-collection.page');
        $this->authorizePermission($request, 'shipper-collection.view');

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => [Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
            'approval_status' => ['nullable', Rule::in(['PENDING', 'APPROVED', 'REJECTED'])],
            'shipper_user_id' => ['nullable', 'exists:users,id'],
            'search' => ['nullable', 'string'],
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

        $precomputedPermissions = $this->precomputeVisibleColumns($request);

        $collections = ShipperCollection::query()
            ->forUserRole()
            ->select('shipper_collections.*')
            ->with(['shipper:id,name'])
            ->withCount('orders')
            ->withSum('orders as current_total_amount', 'total_amount')
            ->withSum('orders as total_order_fees', 'shipping_fee')
            ->withSum('orders as current_shipper_fees', 'commission_amount')
            ->when(
                $statuses !== [],
                fn (Builder $query): Builder => $query->whereIn('status', $statuses)
            )
            ->when(
                $validated['approval_status'] ?? null,
                fn (Builder $query, string $status): Builder => $query->where('approval_status', $status)
            )
            ->when(
                $validated['shipper_user_id'] ?? null,
                fn (Builder $query, int|string $id): Builder => $query->where('shipper_user_id', $id)
            )
            ->when(
                $validated['q'] ?? $validated['search'] ?? null,
                function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $builder) use ($search): void {
                        $builder->where('id', 'like', "%{$search}%")
                            ->orWhereHas('shipper', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('orders', fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('external_code', 'like', "%{$search}%"));
                    });
                }
            )
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 100))
            ->appends($request->query());

        return response()->json([
            'data' => collect($collections->items())->map(function (ShipperCollection $collection) use ($request, $precomputedPermissions): array {
                $filteredData = $this->filterVisibleColumns($request, $collection, $precomputedPermissions);
                $totals = $this->calculateCollectionTotalsFromOrderSums(
                    (float) ($collection->current_total_amount ?? 0),
                    (float) ($collection->current_shipper_fees ?? 0),
                    (int) ($collection->orders_count ?? 0),
                );

                if (array_key_exists('total_amount', $filteredData)) {
                    $filteredData['total_amount'] = $totals['total_amount'];
                }

                if (array_key_exists('shipper_fees', $filteredData)) {
                    $filteredData['shipper_fees'] = $totals['shipper_fees'];
                }

                if (array_key_exists('net_amount', $filteredData)) {
                    $filteredData['net_amount'] = $totals['net_amount'];
                }

                if (array_key_exists('number_of_orders', $filteredData)) {
                    $filteredData['number_of_orders'] = $totals['number_of_orders'];
                }

                // shipping_fee column is computed via withSum, not stored in the table
                $filteredData['shipping_fee'] = (float) ($collection->total_order_fees ?? 0);

                if (array_key_exists('fees', $filteredData)) {
                    $filteredData['fees'] = $totals['shipper_fees'];
                }

                return $filteredData;
            })->values(),
            'meta' => [
                'current_page' => $collections->currentPage(),
                'per_page'     => $collections->perPage(),
                'last_page'    => $collections->lastPage(),
                'total'        => $collections->total(),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $this->authorizePermission($request, 'shipper-collection.export');

        $ids = $request->input('ids');
        if ($ids && is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $query = ShipperCollection::query();
        if ($ids && is_array($ids)) {
            $query->whereIn('id', $ids);
        }

        $exportCollections = (clone $query)
            ->with(['shipper:id,name', 'orders:id,total_amount,commission_amount'])
            ->get();

        $totalAmount = round((float) $exportCollections->sum(function (ShipperCollection $collection): float {
            $totalAmount = (float) $collection->orders->sum('total_amount');
            $shipperFees = (float) $collection->orders->sum('commission_amount');

            return $totalAmount - $shipperFees;
        }), 2);

        $shippers = $exportCollections->pluck('shipper.name')->filter()->unique();
        $namePart = ($shippers->count() === 1) ? " - " . $shippers->first() : "";

        $date = now()->format('d-m-y');
        $filename = "تحصيل مندوب{$namePart} - {$totalAmount} - {$date}.xlsx";

        return Excel::download(new CollectedShippersExport($ids ? null : $query, $ids ? $ids : null), $filename);
    }

    public function bulkStatus(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-collection.update');

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:shipper_collections,id'],
            'status' => ['required', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
        ]);

        DB::transaction(function () use ($data) {
            $collections = ShipperCollection::whereIn('id', $data['ids'])->get();
            foreach ($collections as $collection) {
                // We use the model instance to trigger any events/checks
                $collection->update(['status' => $data['status']]);
                $this->syncCollectionOrdersState($collection);
            }
        });

        return response()->json(['message' => 'Status updated for selected collections.']);
    }

    public function eligibleOrders(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-collection.page');
        $this->authorizePermission($request, 'shipper-collection.view');

        $validated = $request->validate([
            'shipper_user_id' => ['nullable', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 1000000);

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
                'commission_amount',
                'company_amount',
                'cod_amount',
                'shipping_fee',
                'status',
                'shipper_user_id',
                'client_user_id',
                'approval_status',
                'is_shipper_collected',
                'shipper_collected_at',
            ])
            ->with(['shipper:id,name', 'client:id,name,phone'])
            ->whereIn('status', self::ELIGIBLE_ORDER_STATUSES)
            ->where('approval_status', 'APPROVED')
            ->whereNotNull('shipper_user_id')
            ->whereDoesntHave('shipperCollections', function ($q) {
                $q->where('shipper_collections.status', '!=', 'CANCELLED');
            })
            ->where('is_shipper_collected', false)
            ->when(
                $validated['shipper_user_id'] ?? null,
                fn (Builder $query, int|string $shipperUserId): Builder => $query->where('shipper_user_id', $shipperUserId)
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
                'only_collectible_orders' => true,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-collection.create');
        $this->checkWorkingHours('orders');

        $data = $request->validate([
            'shipper_user_id' => ['nullable', 'exists:users,id'],
            // collection_date is optional, will be set to today if not provided
            'collection_date' => ['nullable', 'date'],
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'distinct', 'exists:orders,id'],
        ]);

        if ($request->user()?->hasRole('shipper')) {
            $data['shipper_user_id'] = $request->user()->id;
        }

        if (empty($data['shipper_user_id'])) {
            throw ValidationException::withMessages([
                'shipper_user_id' => ['Please select a shipper.'],
            ]);
        }

        // If collection_date is not provided, set it to today
        if (empty($data['collection_date'])) {
            $data['collection_date'] = now()->toDateString();
        }

        $orderIds = array_values(array_unique($data['order_ids']));
        $orders = $this->resolveEligibleCollectionOrders($data['shipper_user_id'], $orderIds);

        $totalAmount = $orders->sum('total_amount');
        $shipperFees = round((float) $orders->sum('commission_amount'), 2);
        $netAmount = (float) $totalAmount - $shipperFees;

        $creatorId = $request->user()->id;
        $canApproveOnCreate = $request->user()?->can('shipper-collection.approve') ?? false;

        $collectionData = [
            'shipper_user_id' => $data['shipper_user_id'],
            'collection_date' => $data['collection_date'],
            'total_amount' => $totalAmount,
            'number_of_orders' => $orders->count(),
            'shipper_fees' => $shipperFees,
            'net_amount' => $netAmount,
            'status' => 'PENDING',
            'approval_status' => $canApproveOnCreate ? 'APPROVED' : 'PENDING',
            'created_by' => $creatorId,
            'approved_by' => $canApproveOnCreate ? $creatorId : null,
            'approved_at' => $canApproveOnCreate ? now() : null,
        ];

        $collection = DB::transaction(function () use ($collectionData, $orders): ShipperCollection {
            $collection = ShipperCollection::query()->create($collectionData);
            $this->attachOrdersToCollection($collection, $orders);
            $this->syncCollectionOrdersState($collection);

            return $collection;
        });

        return response()->json([
            'message' => 'Shipper collection created successfully.',
            'data' => $this->filterVisibleColumns($request, $collection->load(['shipper:id,name'])->loadCount('orders')),
        ], 201);
    }

    public function show(Request $request, ShipperCollection $shipperCollection): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-collection.page');
        $this->authorizePermission($request, 'shipper-collection.view');

        return response()->json($this->collectionDetailsPayload($request, $shipperCollection));
    }

    public function update(Request $request, ShipperCollection $shipperCollection): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-collection.update');

        $data = $request->validate([
            'shipper_user_id' => ['sometimes', 'required', 'exists:users,id'],
            'collection_date' => ['sometimes', 'required', 'date'],
            'total_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'shipper_fees' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'net_amount' => ['sometimes', 'nullable', 'numeric'],
            'status' => ['sometimes', 'required', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));
        $this->ensureCollectionCanBeCompleted($shipperCollection, $data['status'] ?? null);
        $this->authorizeUnlockCollection($request, $shipperCollection, $data['status'] ?? null);

        DB::transaction(function () use ($shipperCollection, $data): void {
            $shipperCollection->update($data);
            $this->syncCollectionOrdersState($shipperCollection);
        });

        return response()->json([
            'message' => 'Shipper collection updated successfully.',
            'data' => $this->filterVisibleColumns($request, $shipperCollection),
        ]);
    }

    public function destroy(Request $request, ShipperCollection $shipperCollection): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-collection.delete');
        $this->authorizeUnlockCollection($request, $shipperCollection, 'DELETE');

        DB::transaction(function () use ($shipperCollection): void {
            $this->syncCollectionOrdersState($shipperCollection, null, true);
            $shipperCollection->delete();
        });

        return response()->json([
            'message' => 'Shipper collection deleted successfully.',
        ]);
    }

    public function approve(Request $request, ShipperCollection $shipperCollection): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-collection.approve');

        $data = $request->validate([
            'approval_note' => ['nullable', 'string'],
        ]);

        $shipperCollection->update([
            'approval_status' => 'APPROVED',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'approval_note' => $data['approval_note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Shipper collection approved successfully.',
            'data' => $this->filterVisibleColumns($request, $shipperCollection->fresh()),
        ]);
    }

    public function reject(Request $request, ShipperCollection $shipperCollection): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-collection.reject');

        $data = $request->validate([
            'approval_note' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $shipperCollection, $data): void {
            $shipperCollection->update([
                'approval_status' => 'REJECTED',
                'rejected_by' => $request->user()->id,
                'rejected_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'approval_note' => $data['approval_note'] ?? null,
                'status' => 'CANCELLED',
            ]);

            $this->syncCollectionOrdersState($shipperCollection);
        });

        return response()->json([
            'message' => 'Shipper collection rejected successfully.',
            'data' => $this->filterVisibleColumns($request, $shipperCollection->fresh()),
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function authorizeEditableColumns(Request $request, array $columns): void
    {
        foreach ($columns as $column) {
            $permission = CollectionsReturnsSettlementsPermissionMap::SHIPPER_COLLECTION_EDIT_COLUMNS[$column] ?? null;

            if ($permission && ! $request->user()?->can($permission)) {
                abort(403, "Missing permission: {$permission}");
            }
        }
    }

    private function ensureCollectionCanBeCompleted(ShipperCollection $collection, ?string $nextStatus): void
    {
        if ($nextStatus !== 'COMPLETED') {
            return;
        }

        if ($collection->approval_status !== 'APPROVED') {
            throw ValidationException::withMessages([
                'status' => ['Shipper collection must be approved before it can be completed.'],
            ]);
        }
    }

    private function authorizeUnlockCollection(Request $request, ShipperCollection $collection, ?string $nextStatus): void
    {
        if (! $this->willUnlockCollection($collection, $nextStatus)) {
            return;
        }

        $this->authorizePermission($request, 'shipper-collection.unlock');
    }

    private function willUnlockCollection(ShipperCollection $collection, ?string $nextStatus): bool
    {
        if ($nextStatus === 'DELETE') {
            return $collection->orders()->exists();
        }

        if ($nextStatus === null || $nextStatus === $collection->status) {
            return false;
        }

        if ($nextStatus === 'CANCELLED') {
            return true;
        }

        return $collection->status === 'COMPLETED' && $nextStatus !== 'COMPLETED';
    }

    private function precomputeVisibleColumns(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            return [];
        }

        $result = [];
        foreach (CollectionsReturnsSettlementsPermissionMap::SHIPPER_COLLECTION_VIEW_COLUMNS as $column => $permission) {
            $result[$column] = ($permission === null || $user->can($permission));
        }

        return $result;
    }

    private function filterVisibleColumns(Request $request, ShipperCollection $collection, ?array $precomputedPermissions = null): array
    {
        $payload = $collection->toArray();
        $result = [];

        foreach (CollectionsReturnsSettlementsPermissionMap::SHIPPER_COLLECTION_VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            $hasAccess = $precomputedPermissions
                ? ($precomputedPermissions[$column] ?? false)
                : ($permission === null || $request->user()?->can($permission));

            if ($hasAccess) {
                $result[$column] = $payload[$column];
            }
        }

        if (! array_key_exists('id', $result)) {
            $result['id'] = $collection->id;
        }

        return $result;
    }

    private function formatEligibleOrder(Order $order): array
    {
        $shipperCollectedAt = $order->shipper_collected_at;

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
            'collection_amount' => $this->resolveCollectionAmount($order),
            'status' => $order->status,
            'shipper_user_id' => $order->shipper_user_id,
            'shipper_name' => $order->shipper?->name,
            'client_name' => $order->client?->name,
            'client' => [
                'name' => $order->client?->name,
                'phone' => $order->client?->phone,
            ],
            'is_shipper_collected' => (bool) $order->is_shipper_collected,
            'shipper_collected_at' => $shipperCollectedAt instanceof \DateTimeInterface ? $shipperCollectedAt->format('Y-m-d') : ($shipperCollectedAt !== null ? (string) $shipperCollectedAt : null),
        ];
    }

    private function resolveEligibleCollectionOrders(int $shipperUserId, array $orderIds): Collection
    {
        $orders = Order::query()
            ->forUserRole()
            ->select([
                'id',
                'shipper_user_id',
                'total_amount',
                'commission_amount',
                'company_amount',
                'shipping_fee',
                'status',
                'is_shipper_collected',
            ])
            ->where('shipper_user_id', $shipperUserId)
            ->whereIn('id', $orderIds)
            ->whereIn('status', self::ELIGIBLE_ORDER_STATUSES)
            ->where('is_shipper_collected', false)
            ->whereDoesntHave('shipperCollections', function ($q) {
                $q->where('shipper_collections.status', '!=', 'CANCELLED');
            })
            ->get();

        if ($orders->count() !== count($orderIds)) {
            throw ValidationException::withMessages([
                'order_ids' => ['One or more selected orders are not eligible for shipper collection.'],
            ]);
        }

        return $orders;
    }

    private function attachOrdersToCollection(ShipperCollection $collection, Collection $orders): void
    {
        $timestamp = now();
        $rows = $orders
            ->map(fn (Order $order): array => [
                'shipper_collection_id' => $collection->id,
                'order_id' => $order->id,
                'order_amount' => $order->total_amount,
                'shipper_fee' => $order->commission_amount,
                'net_amount' => $this->resolveCollectionAmount($order),
                'added_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->all();

        if ($rows !== []) {
            ShipperCollectionOrder::query()->insert($rows);
        }
    }

    private function syncCollectionOrdersState(ShipperCollection $collection, ?array $orderIds = null, bool $reset = false): void
    {
        $orderIds ??= $collection->orders()->pluck('orders.id')->all();

        if ($orderIds === []) {
            return;
        }

        if ($reset || $collection->status === 'CANCELLED') {
            Order::query()
                ->whereIn('id', $orderIds)
                ->update([
                    'is_shipper_collected' => false,
                    'shipper_collected_at' => null,
                ]);

            return;
        }

        Order::query()
            ->whereIn('id', $orderIds)
            ->update([
                'is_shipper_collected' => $collection->status === 'COMPLETED',
                'shipper_collected_at' => $collection->status === 'COMPLETED' ? $collection->collection_date : null,
            ]);
    }

    private function resolveCollectionAmount(Order $order): float
    {
        $amount = app(FinancialFormulaService::class)->calculate('formula_shipper_collection_net_amount', [
            'total_amount' => (float) $order->total_amount,
            'shipping_fee' => (float) $order->shipping_fee,
            'commission_amount' => (float) $order->commission_amount,
            'company_amount' => (float) $order->company_amount,
            'cod_amount' => (float) $order->cod_amount,
            'settlement_fees' => 0,
        ]);

        return round($amount, 2);
    }

    public function removeOrder(Request $request, ShipperCollection $shipperCollection, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-collection.update');

        DB::transaction(function () use ($shipperCollection, $order) {
            $pivot = ShipperCollectionOrder::where('shipper_collection_id', $shipperCollection->id)
                ->where('order_id', $order->id)
                ->first();

            if ($pivot) {
                // Delete pivot record
                $pivot->delete();

                if (! $shipperCollection->orders()->exists()) {
                    $shipperCollection->delete();
                } else {
                    $this->refreshStoredCollectionTotals($shipperCollection);
                }

                // Reset order state
                $order->update([
                    'is_shipper_collected' => false,
                    'shipper_collected_at' => null,
                ]);
            }
        });

        if (!ShipperCollection::where('id', $shipperCollection->id)->exists()) {
            return response()->json([
                'message' => 'Collection deleted because it had no more orders.',
                'deleted' => true
            ]);
        }

        return response()->json([
            'message' => 'Order removed from collection.',
            'data' => $this->collectionDetailsPayload($request, $shipperCollection->fresh()),
        ]);
    }

    private function loadCollectionOrderRelations(ShipperCollection $shipperCollection): ShipperCollection
    {
        return $shipperCollection->load([
            'shipper:id,name',
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
                'orders.is_client_settled',
                'orders.order_note',
                'orders.latest_status_note',
            ]),
            'orders.client:id,name,phone',
        ]);
    }

    private function collectionDetailsPayload(Request $request, ShipperCollection $shipperCollection): array
    {
        $this->loadCollectionOrderRelations($shipperCollection);

        $totals = $this->calculateCollectionTotalsFromOrders($shipperCollection->orders);

        $result = $this->filterVisibleColumns($request, $shipperCollection);

        // Keep list and detail screens on the same live order-based calculation.
        if (array_key_exists('total_amount', $result)) {
            $result['total_amount'] = $totals['total_amount'];
        }

        if (array_key_exists('shipper_fees', $result)) {
            $result['shipper_fees'] = $totals['shipper_fees'];
        }

        if (array_key_exists('net_amount', $result)) {
            $result['net_amount'] = $totals['net_amount'];
        }

        if (array_key_exists('fees', $result)) {
            $result['fees'] = $totals['shipper_fees'];
        }

        if (array_key_exists('number_of_orders', $result)) {
            $result['number_of_orders'] = $totals['number_of_orders'];
        }

        $result['orders'] = $shipperCollection->orders;

        return $result;
    }

    private function refreshStoredCollectionTotals(ShipperCollection $collection): void
    {
        $totals = $this->calculateCollectionTotalsFromQuery($collection);

        $collection->update([
            'total_amount' => $totals['total_amount'],
            'shipper_fees' => $totals['shipper_fees'],
            'net_amount' => $totals['net_amount'],
            'number_of_orders' => $totals['number_of_orders'],
        ]);
    }

    private function calculateCollectionTotalsFromQuery(ShipperCollection $collection): array
    {
        $totals = ShipperCollectionOrder::query()
            ->join('orders', 'orders.id', '=', 'shipper_collection_orders.order_id')
            ->where('shipper_collection_orders.shipper_collection_id', $collection->id)
            ->selectRaw('COALESCE(SUM(orders.total_amount), 0) as total_amount, COALESCE(SUM(orders.commission_amount), 0) as shipper_fees, COUNT(orders.id) as number_of_orders')
            ->first();

        return $this->calculateCollectionTotalsFromOrderSums(
            (float) ($totals->total_amount ?? 0),
            (float) ($totals->shipper_fees ?? 0),
            (int) ($totals->number_of_orders ?? 0),
        );
    }

    private function calculateCollectionTotalsFromOrders(Collection $orders): array
    {
        return $this->calculateCollectionTotalsFromOrderSums(
            (float) $orders->sum('total_amount'),
            (float) $orders->sum('commission_amount'),
            $orders->count(),
        );
    }

    private function calculateCollectionTotalsFromOrderSums(float $totalAmount, float $shipperFees, int $numberOfOrders): array
    {
        $totalAmount = round($totalAmount, 2);
        $shipperFees = round($shipperFees, 2);

        return [
            'total_amount' => $totalAmount,
            'shipper_fees' => $shipperFees,
            'net_amount' => round($totalAmount - $shipperFees, 2),
            'number_of_orders' => $numberOfOrders,
        ];
    }

    private function resolveShipperFees(ShipperCollection $collection): float
    {
        if (isset($collection->pivot_shipper_fees)) {
            return round((float) $collection->pivot_shipper_fees, 2);
        }

        // الـ pivot->shipper_fee قد تكون null عندما يُستخدم select() مخصص في الـ eager load
        // لذلك نتحقق أولاً من الـ pivot إن كانت محمّلة وغير فارغة
        if ($collection->relationLoaded('orders') && $collection->orders->isNotEmpty()) {
            $fromPivot = (float) $collection->orders->sum(
                fn (Order $order): float => (float) ($order->pivot?->shipper_fee ?? 0)
            );

            if ($fromPivot > 0) {
                return round($fromPivot, 2);
            }
        }

        // Fallback: قراءة مجموع shipper_fee مباشرة من جدول الـ pivot
        $pivotSum = ShipperCollectionOrder::query()
            ->where('shipper_collection_id', $collection->id)
            ->sum('shipper_fee');

        if ($pivotSum > 0) {
            return round((float) $pivotSum, 2);
        }

        return round((float) ($collection->shipper_fees ?? 0), 2);
    }
}
