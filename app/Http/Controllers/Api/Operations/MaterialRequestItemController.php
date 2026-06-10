<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Support\Permissions\OperationsPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialRequestItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'material-request-item.page');
        $this->authorizePermission($request, 'material-request-item.view');

        $rows = MaterialRequestItem::query()
            ->with(['material:id,name,code'])
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $rows->map(fn (MaterialRequestItem $row): array => $this->filterVisibleColumns($request, $row))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'material-request-item.create');

        $data = $request->validate([
            'material_request_id' => ['required', 'integer', 'exists:material_requests,id'],
            'material_id' => ['required', 'integer', 'exists:materials,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data), OperationsPermissionMap::MATERIAL_REQUEST_ITEM_EDIT_COLUMNS);
        $this->authorizeEditableColumns($request, ['price', 'total'], OperationsPermissionMap::MATERIAL_REQUEST_ITEM_EDIT_COLUMNS);

        $row = DB::transaction(function () use ($data): MaterialRequestItem {
            $material = Material::query()->lockForUpdate()->findOrFail($data['material_id']);

            if ($material->stock < $data['quantity']) {
                abort(422, 'Insufficient material stock.');
            }

            $price = (float) $material->sale_price;
            $total = $price * (int) $data['quantity'];

            $row = MaterialRequestItem::query()->create([
                ...$data,
                'price' => $price,
                'total' => $total,
            ]);

            $material->decrement('stock', (int) $data['quantity']);
            $this->recalculateMaterialRequestTotals((int) $data['material_request_id']);

            return $row;
        });

        $row->load(['material:id,name,code']);

        return response()->json([
            'message' => 'Material request item created successfully.',
            'data' => $this->filterVisibleColumns($request, $row),
        ], 201);
    }

    public function show(Request $request, MaterialRequestItem $materialRequestItem): JsonResponse
    {
        $this->authorizePermission($request, 'material-request-item.page');
        $this->authorizePermission($request, 'material-request-item.view');

        $materialRequestItem->load(['material:id,name,code']);

        return response()->json($this->filterVisibleColumns($request, $materialRequestItem));
    }

    public function update(Request $request, MaterialRequestItem $materialRequestItem): JsonResponse
    {
        $this->authorizePermission($request, 'material-request-item.update');

        $data = $request->validate([
            'material_request_id' => ['sometimes', 'required', 'integer', 'exists:material_requests,id'],
            'material_id' => ['sometimes', 'required', 'integer', 'exists:materials,id'],
            'quantity' => ['sometimes', 'required', 'integer', 'min:1'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data), OperationsPermissionMap::MATERIAL_REQUEST_ITEM_EDIT_COLUMNS);
        $this->authorizeEditableColumns($request, ['price', 'total'], OperationsPermissionMap::MATERIAL_REQUEST_ITEM_EDIT_COLUMNS);

        DB::transaction(function () use ($materialRequestItem, $data): void {
            $oldMaterialId = (int) $materialRequestItem->material_id;
            $oldQuantity = (int) $materialRequestItem->quantity;
            $oldRequestId = (int) $materialRequestItem->material_request_id;

            $newMaterialId = (int) ($data['material_id'] ?? $oldMaterialId);
            $newQuantity = (int) ($data['quantity'] ?? $oldQuantity);
            $newRequestId = (int) ($data['material_request_id'] ?? $oldRequestId);

            if ($newMaterialId !== $oldMaterialId) {
                $oldMaterial = Material::query()->lockForUpdate()->findOrFail($oldMaterialId);
                $oldMaterial->increment('stock', $oldQuantity);

                $newMaterial = Material::query()->lockForUpdate()->findOrFail($newMaterialId);
                if ($newMaterial->stock < $newQuantity) {
                    abort(422, 'Insufficient material stock.');
                }
                $newMaterial->decrement('stock', $newQuantity);

                $newPrice = (float) $newMaterial->sale_price;
            } else {
                $material = Material::query()->lockForUpdate()->findOrFail($newMaterialId);

                $delta = $newQuantity - $oldQuantity;
                if ($delta > 0 && $material->stock < $delta) {
                    abort(422, 'Insufficient material stock.');
                }

                if ($delta > 0) {
                    $material->decrement('stock', $delta);
                } elseif ($delta < 0) {
                    $material->increment('stock', abs($delta));
                }

                $newPrice = (float) $material->sale_price;
            }

            $materialRequestItem->update([
                'material_request_id' => $newRequestId,
                'material_id' => $newMaterialId,
                'quantity' => $newQuantity,
                'price' => $newPrice,
                'total' => $newPrice * $newQuantity,
            ]);

            $this->recalculateMaterialRequestTotals($oldRequestId);
            if ($newRequestId !== $oldRequestId) {
                $this->recalculateMaterialRequestTotals($newRequestId);
            }
        });

        $materialRequestItem->load(['material:id,name,code']);

        return response()->json([
            'message' => 'Material request item updated successfully.',
            'data' => $this->filterVisibleColumns($request, $materialRequestItem),
        ]);
    }

    public function destroy(Request $request, MaterialRequestItem $materialRequestItem): JsonResponse
    {
        $this->authorizePermission($request, 'material-request-item.delete');

        DB::transaction(function () use ($materialRequestItem): void {
            $material = Material::query()->lockForUpdate()->find($materialRequestItem->material_id);
            if ($material) {
                $material->increment('stock', (int) $materialRequestItem->quantity);
            }

            $requestId = (int) $materialRequestItem->material_request_id;
            $materialRequestItem->delete();

            $this->recalculateMaterialRequestTotals($requestId);
        });

        return response()->json([
            'message' => 'Material request item deleted successfully.',
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
    private function filterVisibleColumns(Request $request, MaterialRequestItem $row): array
    {
        $payload = $row->toArray();
        $result = [];

        foreach (OperationsPermissionMap::MATERIAL_REQUEST_ITEM_VIEW_COLUMNS as $column => $permission) {
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

    private function recalculateMaterialRequestTotals(int $materialRequestId): void
    {
        $materialsTotal = (float) MaterialRequestItem::query()
            ->where('material_request_id', $materialRequestId)
            ->sum('total');

        MaterialRequest::query()
            ->whereKey($materialRequestId)
            ->update(['materials_total' => $materialsTotal]);
    }
}
