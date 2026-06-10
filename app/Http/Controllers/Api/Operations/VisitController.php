<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;
use App\Models\PickupRequest;
use App\Models\Visit;
use App\Support\Permissions\OperationsPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'visit.page');
        $this->authorizePermission($request, 'visit.view');

        $rows = Visit::query()
            ->forUserRole()
            ->with(['shipper:id,name', 'client:id,name', 'pickupRequest:id,status', 'materialRequest:id,status'])
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $rows->map(fn (Visit $row): array => $this->filterVisibleColumns($request, $row))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'visit.create');

        $data = $request->validate([
            'shipper_id' => ['required', 'integer', 'exists:users,id'],
            'client_id' => ['required', 'integer', 'exists:users,id'],
            'pickup_request_id' => ['nullable', 'integer', 'exists:pickup_requests,id'],
            'material_request_id' => ['nullable', 'integer', 'exists:material_requests,id'],
            'visit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data), OperationsPermissionMap::VISIT_EDIT_COLUMNS);

        $row = DB::transaction(function () use ($data): Visit {
            [$pickupRequest, $materialRequest] = $this->resolveLinkedRequests(
                $data['pickup_request_id'] ?? null,
                $data['material_request_id'] ?? null
            );

            $this->assertSingleClientContext(
                $data['client_id'],
                $pickupRequest,
                $materialRequest
            );

            $row = Visit::query()->create($data);
            $this->applyCombinedShippingRules($row, $pickupRequest, $materialRequest, $data['visit_cost'] ?? null);

            return $row;
        });

        $row->load(['shipper:id,name', 'client:id,name', 'pickupRequest:id,status', 'materialRequest:id,status']);

        return response()->json([
            'message' => 'Visit created successfully.',
            'data' => $this->filterVisibleColumns($request, $row),
        ], 201);
    }

    public function show(Request $request, Visit $visit): JsonResponse
    {
        $this->authorizePermission($request, 'visit.page');
        $this->authorizePermission($request, 'visit.view');

        $visit->load(['shipper:id,name', 'client:id,name', 'pickupRequest:id,status', 'materialRequest:id,status']);

        return response()->json($this->filterVisibleColumns($request, $visit));
    }

    public function update(Request $request, Visit $visit): JsonResponse
    {
        $this->authorizePermission($request, 'visit.update');

        $data = $request->validate([
            'shipper_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'client_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'pickup_request_id' => ['nullable', 'integer', 'exists:pickup_requests,id'],
            'material_request_id' => ['nullable', 'integer', 'exists:material_requests,id'],
            'visit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data), OperationsPermissionMap::VISIT_EDIT_COLUMNS);

        DB::transaction(function () use ($visit, $data): void {
            $pickupRequestId = array_key_exists('pickup_request_id', $data)
                ? $data['pickup_request_id']
                : $visit->pickup_request_id;

            $materialRequestId = array_key_exists('material_request_id', $data)
                ? $data['material_request_id']
                : $visit->material_request_id;

            [$pickupRequest, $materialRequest] = $this->resolveLinkedRequests($pickupRequestId, $materialRequestId);

            $resolvedClientId = array_key_exists('client_id', $data)
                ? $data['client_id']
                : $visit->client_id;

            $this->assertSingleClientContext($resolvedClientId, $pickupRequest, $materialRequest);

            $data['client_id'] = $resolvedClientId;
            $visit->update($data);

            $requestedVisitCost = array_key_exists('visit_cost', $data)
                ? $data['visit_cost']
                : $visit->visit_cost;

            $this->applyCombinedShippingRules($visit, $pickupRequest, $materialRequest, $requestedVisitCost);
        });

        $visit->load(['shipper:id,name', 'client:id,name', 'pickupRequest:id,status', 'materialRequest:id,status']);

        return response()->json([
            'message' => 'Visit updated successfully.',
            'data' => $this->filterVisibleColumns($request, $visit),
        ]);
    }

    public function destroy(Request $request, Visit $visit): JsonResponse
    {
        $this->authorizePermission($request, 'visit.delete');

        $visit->delete();

        return response()->json([
            'message' => 'Visit deleted successfully.',
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<string, string>  $columnPermissions
     */
    private function authorizeEditableColumns(Request $request, array $columns, array $columnPermissions): void
    {
        foreach ($columns as $column) {
            $permission = $columnPermissions[$column] ?? null;

            if ($permission && ! $request->user()?->can($permission)) {
                abort(403, "Missing permission: {$permission}");
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function filterVisibleColumns(Request $request, Visit $row): array
    {
        $payload = $row->toArray();
        $result = [];

        foreach (OperationsPermissionMap::VISIT_VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $payload[$column];
            }
        }

        if (! array_key_exists('id', $result)) {
            $result['id'] = $row->id;
        }

        return $result;
    }

    /**
     * @return array{0: ?PickupRequest, 1: ?MaterialRequest}
     */
    private function resolveLinkedRequests(?int $pickupRequestId, ?int $materialRequestId): array
    {
        $pickupRequest = $pickupRequestId
            ? PickupRequest::query()->find($pickupRequestId)
            : null;

        $materialRequest = $materialRequestId
            ? MaterialRequest::query()->find($materialRequestId)
            : null;

        return [$pickupRequest, $materialRequest];
    }

    private function assertSingleClientContext(int $clientId, ?PickupRequest $pickupRequest, ?MaterialRequest $materialRequest): void
    {
        if ($pickupRequest && $pickupRequest->client_id !== $clientId) {
            abort(422, 'Pickup request belongs to a different client.');
        }

        if ($materialRequest && $materialRequest->client_id !== $clientId) {
            abort(422, 'Material request belongs to a different client.');
        }
    }

    private function applyCombinedShippingRules(
        Visit $visit,
        ?PickupRequest $pickupRequest,
        ?MaterialRequest $materialRequest,
        float|int|string|null $requestedVisitCost
    ): void {
        if ($pickupRequest && $materialRequest && $pickupRequest->client_id === $materialRequest->client_id) {
            // Shipping fee source is pickup request; material shipping is zeroed to avoid double charge.
            $shippingFee = (float) ($pickupRequest->pickup_cost ?? 0);

            if ($shippingFee <= 0) {
                $shippingFee = max(
                    (float) ($materialRequest->shipping_cost ?? 0),
                    (float) ($requestedVisitCost ?? 0)
                );
            }

            $pickupRequest->update([
                'combined_with_material' => true,
                'pickup_cost' => $shippingFee,
            ]);

            $materialRequest->update([
                'combined_visit' => true,
                'shipping_cost' => 0,
            ]);

            $visit->update([
                'visit_cost' => $shippingFee,
            ]);

            return;
        }

        if ($pickupRequest) {
            $pickupRequest->update([
                'combined_with_material' => false,
            ]);
        }

        if ($materialRequest) {
            $materialRequest->update([
                'combined_visit' => false,
            ]);
        }
    }
}
