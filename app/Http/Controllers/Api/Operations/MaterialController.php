<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Support\Permissions\OperationsPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'material.page');
        $this->authorizePermission($request, 'material.view');

        $materials = Material::query()->orderByDesc('id')->get();

        return response()->json(
            $materials->map(fn (Material $material): array => $this->filterVisibleColumns($request, $material))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'material.create');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255', 'unique:materials,code'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data), OperationsPermissionMap::MATERIAL_EDIT_COLUMNS);

        $material = Material::query()->create($data);

        return response()->json([
            'message' => 'Material created successfully.',
            'data' => $this->filterVisibleColumns($request, $material),
        ], 201);
    }

    public function show(Request $request, Material $material): JsonResponse
    {
        $this->authorizePermission($request, 'material.page');
        $this->authorizePermission($request, 'material.view');

        return response()->json($this->filterVisibleColumns($request, $material));
    }

    public function update(Request $request, Material $material): JsonResponse
    {
        $this->authorizePermission($request, 'material.update');

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255', Rule::unique('materials', 'code')->ignore($material->id)],
            'cost_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'sale_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data), OperationsPermissionMap::MATERIAL_EDIT_COLUMNS);

        $material->update($data);

        return response()->json([
            'message' => 'Material updated successfully.',
            'data' => $this->filterVisibleColumns($request, $material),
        ]);
    }

    public function destroy(Request $request, Material $material): JsonResponse
    {
        $this->authorizePermission($request, 'material.delete');

        $material->delete();

        return response()->json([
            'message' => 'Material deleted successfully.',
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
    private function filterVisibleColumns(Request $request, Material $material): array
    {
        $payload = $material->toArray();
        $result = [];

        foreach (OperationsPermissionMap::MATERIAL_VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $payload[$column];
            }
        }

        if (! array_key_exists('id', $result)) {
            $result['id'] = $material->id;
        }

        return $result;
    }
}
