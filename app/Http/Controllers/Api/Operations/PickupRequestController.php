<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use App\Support\Operations\VisitAutoSyncService;
use App\Support\Permissions\OperationsPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Traits\ChecksWorkingHours;

class PickupRequestController extends Controller
{
    use ChecksWorkingHours;
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'pickup-request.page');
        $this->authorizePermission($request, 'pickup-request.view');

        $rows = PickupRequest::query()
            ->forUserRole()
            ->with(['client:id,name', 'shipper:id,name', 'createdBy:id,name', 'approvedBy:id,name', 'rejectedBy:id,name', 'visit:id,pickup_request_id'])
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $rows->map(fn (PickupRequest $row): array => $this->filterVisibleColumns($request, $row))->values()
        );
    }

    public function init(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'pickup-request.page');

        return response()->json([
            'metadata' => [
                'working_hours' => \App\Models\Setting::query()->where('group', 'working_hours')->pluck('value', 'key')->all(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'pickup-request.create');
        $this->checkWorkingHours('pickups');

        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:users,id'],
            'shipper_id' => ['nullable', 'integer', 'exists:users,id'],
            'pickup_date' => ['nullable', 'date'],
            'combined_with_material' => ['nullable', 'boolean'],
            'pickup_cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['PENDING', 'ASSIGNED', 'COMPLETED', 'CANCELLED'])],
            'approval_status' => ['nullable', Rule::in(['PENDING', 'APPROVED', 'REJECTED'])],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['nullable', 'date'],
            'rejected_by' => ['nullable', 'integer', 'exists:users,id'],
            'rejected_at' => ['nullable', 'date'],
            'approval_note' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data), OperationsPermissionMap::PICKUP_REQUEST_EDIT_COLUMNS);

        $row = DB::transaction(function () use ($data): PickupRequest {
            $row = PickupRequest::query()->create($data);
            app(VisitAutoSyncService::class)->syncForPickupRequest($row);

            return $row;
        });

        $row->load(['client:id,name', 'shipper:id,name', 'createdBy:id,name', 'approvedBy:id,name', 'rejectedBy:id,name', 'visit:id,pickup_request_id']);

        return response()->json([
            'message' => 'Pickup request created successfully.',
            'data' => $this->filterVisibleColumns($request, $row),
        ], 201);
    }

    public function show(Request $request, PickupRequest $pickupRequest): JsonResponse
    {
        $this->authorizePermission($request, 'pickup-request.page');
        $this->authorizePermission($request, 'pickup-request.view');

        $pickupRequest->load(['client:id,name', 'shipper:id,name', 'createdBy:id,name', 'approvedBy:id,name', 'rejectedBy:id,name', 'visit:id,pickup_request_id']);

        return response()->json($this->filterVisibleColumns($request, $pickupRequest));
    }

    public function update(Request $request, PickupRequest $pickupRequest): JsonResponse
    {
        $this->authorizePermission($request, 'pickup-request.update');

        $data = $request->validate([
            'client_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'shipper_id' => ['nullable', 'integer', 'exists:users,id'],
            'pickup_date' => ['nullable', 'date'],
            'combined_with_material' => ['nullable', 'boolean'],
            'pickup_cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', Rule::in(['PENDING', 'ASSIGNED', 'COMPLETED', 'CANCELLED'])],
            'approval_status' => ['sometimes', 'required', Rule::in(['PENDING', 'APPROVED', 'REJECTED'])],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['nullable', 'date'],
            'rejected_by' => ['nullable', 'integer', 'exists:users,id'],
            'rejected_at' => ['nullable', 'date'],
            'approval_note' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data), OperationsPermissionMap::PICKUP_REQUEST_EDIT_COLUMNS);

        DB::transaction(function () use ($pickupRequest, $data): void {
            $pickupRequest->update($data);
            app(VisitAutoSyncService::class)->syncForPickupRequest($pickupRequest->fresh());
        });

        $pickupRequest->refresh();
        $pickupRequest->load(['client:id,name', 'shipper:id,name', 'createdBy:id,name', 'approvedBy:id,name', 'rejectedBy:id,name', 'visit:id,pickup_request_id']);

        return response()->json([
            'message' => 'Pickup request updated successfully.',
            'data' => $this->filterVisibleColumns($request, $pickupRequest),
        ]);
    }

    public function destroy(Request $request, PickupRequest $pickupRequest): JsonResponse
    {
        $this->authorizePermission($request, 'pickup-request.delete');

        DB::transaction(function () use ($pickupRequest): void {
            app(VisitAutoSyncService::class)->detachPickupRequest($pickupRequest);
            $pickupRequest->delete();
        });

        return response()->json([
            'message' => 'Pickup request deleted successfully.',
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
    private function filterVisibleColumns(Request $request, PickupRequest $row): array
    {
        $payload = $row->toArray();
        $result = [];

        foreach (OperationsPermissionMap::PICKUP_REQUEST_VIEW_COLUMNS as $column => $permission) {
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

        $result['auto_linked_visit_id'] = $row->visit?->id;

        return $result;
    }
}
