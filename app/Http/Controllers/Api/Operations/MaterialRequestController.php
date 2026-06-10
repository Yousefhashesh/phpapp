<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;
use App\Support\Operations\VisitAutoSyncService;
use App\Support\Permissions\OperationsPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Traits\ChecksWorkingHours;

class MaterialRequestController extends Controller
{
    use ChecksWorkingHours;
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'material-request.page');
        $this->authorizePermission($request, 'material-request.view');

        $rows = MaterialRequest::query()
            ->forUserRole()
            ->with(['items.material:id,name,code', 'client:id,name', 'shipper:id,name', 'createdBy:id,name', 'approvedBy:id,name', 'rejectedBy:id,name', 'visit:id,material_request_id'])
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $rows->map(fn (MaterialRequest $row): array => $this->filterVisibleColumns($request, $row))->values()
        );
    }

    public function init(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'material-request.page');

        return response()->json([
            'metadata' => [
                'working_hours' => \App\Models\Setting::query()->where('group', 'working_hours')->pluck('value', 'key')->all(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'material-request.create');
        $this->checkWorkingHours('material_requests');

        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:users,id'],
            'shipper_id' => ['nullable', 'integer', 'exists:users,id'],
            'delivery_type' => ['nullable', Rule::in(['PICKUP', 'DELIVERY'])],
            'combined_visit' => ['nullable', 'boolean'],
            'materials_total' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['PENDING', 'PROCESSING', 'COMPLETED', 'CANCELLED'])],
            'approval_status' => ['nullable', Rule::in(['PENDING', 'APPROVED', 'REJECTED'])],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['nullable', 'date'],
            'rejected_by' => ['nullable', 'integer', 'exists:users,id'],
            'rejected_at' => ['nullable', 'date'],
            'approval_note' => ['nullable', 'string'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data), OperationsPermissionMap::MATERIAL_REQUEST_EDIT_COLUMNS);

        if (($data['delivery_type'] ?? 'DELIVERY') === 'PICKUP') {
            $data['shipping_cost'] = 0;
            $data['combined_visit'] = false;
        }

        $row = DB::transaction(function () use ($data): MaterialRequest {
            $row = MaterialRequest::query()->create($data);
            app(VisitAutoSyncService::class)->syncForMaterialRequest($row);

            return $row;
        });

        $row->load(['items.material:id,name,code', 'client:id,name', 'shipper:id,name', 'createdBy:id,name', 'approvedBy:id,name', 'rejectedBy:id,name', 'visit:id,material_request_id']);

        return response()->json([
            'message' => 'Material request created successfully.',
            'data' => $this->filterVisibleColumns($request, $row),
        ], 201);
    }

    public function show(Request $request, MaterialRequest $materialRequest): JsonResponse
    {
        $this->authorizePermission($request, 'material-request.page');
        $this->authorizePermission($request, 'material-request.view');

        $materialRequest->load(['items.material:id,name,code', 'client:id,name', 'shipper:id,name', 'createdBy:id,name', 'approvedBy:id,name', 'rejectedBy:id,name', 'visit:id,material_request_id']);

        return response()->json($this->filterVisibleColumns($request, $materialRequest));
    }

    public function update(Request $request, MaterialRequest $materialRequest): JsonResponse
    {
        $this->authorizePermission($request, 'material-request.update');

        $data = $request->validate([
            'client_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'shipper_id' => ['nullable', 'integer', 'exists:users,id'],
            'delivery_type' => ['sometimes', 'required', Rule::in(['PICKUP', 'DELIVERY'])],
            'combined_visit' => ['nullable', 'boolean'],
            'materials_total' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', Rule::in(['PENDING', 'PROCESSING', 'COMPLETED', 'CANCELLED'])],
            'approval_status' => ['sometimes', 'required', Rule::in(['PENDING', 'APPROVED', 'REJECTED'])],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['nullable', 'date'],
            'rejected_by' => ['nullable', 'integer', 'exists:users,id'],
            'rejected_at' => ['nullable', 'date'],
            'approval_note' => ['nullable', 'string'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data), OperationsPermissionMap::MATERIAL_REQUEST_EDIT_COLUMNS);

        $resolvedDeliveryType = $data['delivery_type'] ?? $materialRequest->delivery_type;
        if ($resolvedDeliveryType === 'PICKUP') {
            $data['shipping_cost'] = 0;
            $data['combined_visit'] = false;
        }

        DB::transaction(function () use ($materialRequest, $data): void {
            $materialRequest->update($data);
            app(VisitAutoSyncService::class)->syncForMaterialRequest($materialRequest->fresh());
        });

        $materialRequest->refresh();
        $materialRequest->load(['items.material:id,name,code', 'client:id,name', 'shipper:id,name', 'createdBy:id,name', 'approvedBy:id,name', 'rejectedBy:id,name', 'visit:id,material_request_id']);

        return response()->json([
            'message' => 'Material request updated successfully.',
            'data' => $this->filterVisibleColumns($request, $materialRequest),
        ]);
    }

    public function destroy(Request $request, MaterialRequest $materialRequest): JsonResponse
    {
        $this->authorizePermission($request, 'material-request.delete');

        DB::transaction(function () use ($materialRequest): void {
            app(VisitAutoSyncService::class)->detachMaterialRequest($materialRequest);
            $materialRequest->delete();
        });

        return response()->json([
            'message' => 'Material request deleted successfully.',
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
    private function filterVisibleColumns(Request $request, MaterialRequest $row): array
    {
        $payload = $row->toArray();
        $result = [];

        foreach (OperationsPermissionMap::MATERIAL_REQUEST_VIEW_COLUMNS as $column => $permission) {
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
