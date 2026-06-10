<?php

namespace App\Http\Controllers\Api\Area;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Governorate;
use App\Support\Permissions\AreaPlanPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GovernorateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'area.page');
        $this->authorizePermission($request, 'area.view');

        $governorates = Governorate::query()
            ->with(['cities:id,governorate_id,name', 'defaultShipper:id,name'])
            ->orderBy('name')
            ->get();

        return response()->json(
            $governorates->map(fn (Governorate $governorate): array => $this->filterVisibleGovernorateColumns($request, $governorate))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'area.create');

        $data = $request->validate([
            'name'                    => ['required', 'string', 'max:255', 'unique:governorates,name'],
            'follow_up_hours'         => ['required', 'integer', 'min:0'],
            'default_shipper_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'cities'                  => ['nullable', 'array'],
            'cities.*'                => ['required', 'string', 'max:255', 'distinct'],
        ]);

        $editableColumns = array_keys($data);
        $this->authorizeEditableColumns($request, $editableColumns, AreaPlanPermissionMap::GOVERNORATE_EDIT_COLUMNS);
        if (array_key_exists('cities', $data)) {
            $this->authorizeEditableColumns($request, ['name'], AreaPlanPermissionMap::CITY_EDIT_COLUMNS);
        }

        $governorate = Governorate::query()->create([
            'name'                    => $data['name'],
            'follow_up_hours'         => $data['follow_up_hours'],
            'default_shipper_user_id' => $data['default_shipper_user_id'] ?? null,
        ]);

        if (!empty($data['cities'])) {
            $cityRows = array_map(
                fn (string $city): array => ['name' => $city, 'governorate_id' => $governorate->id],
                array_unique($data['cities'])
            );
            City::query()->insert($cityRows);
        }

        $governorate->load(['cities:id,governorate_id,name', 'defaultShipper:id,name']);

        return response()->json([
            'message' => 'Area created successfully.',
            'data' => $this->filterVisibleGovernorateColumns($request, $governorate),
        ], 201);
    }

    public function show(Request $request, Governorate $governorate): JsonResponse
    {
        $this->authorizePermission($request, 'area.page');
        $this->authorizePermission($request, 'area.view');

        $governorate->load(['cities:id,governorate_id,name', 'defaultShipper:id,name']);

        return response()->json($this->filterVisibleGovernorateColumns($request, $governorate));
    }

    public function update(Request $request, Governorate $governorate): JsonResponse
    {
        $this->authorizePermission($request, 'area.update');

        $data = $request->validate([
            'name'                    => ['required', 'string', 'max:255', Rule::unique('governorates', 'name')->ignore($governorate->id)],
            'follow_up_hours'         => ['required', 'integer', 'min:0'],
            'default_shipper_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'cities'                  => ['nullable', 'array'],
            'cities.*'                => ['required', 'string', 'max:255', 'distinct'],
        ]);

        $editableColumns = array_keys($data);
        $this->authorizeEditableColumns($request, $editableColumns, AreaPlanPermissionMap::GOVERNORATE_EDIT_COLUMNS);
        if (array_key_exists('cities', $data)) {
            $this->authorizeEditableColumns($request, ['name'], AreaPlanPermissionMap::CITY_EDIT_COLUMNS);
        }

        $governorate->update([
            'name'                    => $data['name'],
            'follow_up_hours'         => $data['follow_up_hours'],
            'default_shipper_user_id' => $data['default_shipper_user_id'] ?? null,
        ]);

        if (array_key_exists('cities', $data)) {
            $newCities  = array_unique($data['cities'] ?? []);
            $existingCities = $governorate->cities()->pluck('name')->all();

            $toDelete = array_diff($existingCities, $newCities);
            $toCreate = array_diff($newCities, $existingCities);

            if (!empty($toDelete)) {
                $governorate->cities()->whereIn('name', $toDelete)->delete();
            }
            if (!empty($toCreate)) {
                $cityRows = array_map(
                    fn (string $city): array => ['name' => $city, 'governorate_id' => $governorate->id],
                    array_values($toCreate)
                );
                City::query()->insert($cityRows);
            }
        }

        $governorate->load(['cities:id,governorate_id,name', 'defaultShipper:id,name']);

        return response()->json([
            'message' => 'Area updated successfully.',
            'data' => $this->filterVisibleGovernorateColumns($request, $governorate),
        ]);
    }

    public function destroy(Request $request, Governorate $governorate): JsonResponse
    {
        $this->authorizePermission($request, 'area.delete');

        $governorate->delete();

        return response()->json(['message' => 'Area deleted successfully.']);
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
    private function filterVisibleGovernorateColumns(Request $request, Governorate $governorate): array
    {
        $governorate->loadMissing(['cities:id,governorate_id,name,created_at,updated_at', 'defaultShipper:id,name']);

        $payload = [
            'id' => $governorate->id,
            'name' => $governorate->name,
            'follow_up_hours' => $governorate->follow_up_hours,
            'default_shipper_user_id' => $governorate->default_shipper_user_id,
            'defaultShipper' => $governorate->defaultShipper ? [
                'id' => $governorate->defaultShipper->id,
                'name' => $governorate->defaultShipper->name,
            ] : null,
            'cities' => $governorate->cities
                ->map(fn (City $city): array => $this->filterVisibleCityColumns($request, $city))
                ->values()
                ->all(),
            'created_at' => $governorate->created_at,
            'updated_at' => $governorate->updated_at,
        ];

        foreach (AreaPlanPermissionMap::GOVERNORATE_VIEW_COLUMNS as $column => $permission) {
            if ($permission && ! $request->user()?->can($permission)) {
                unset($payload[$column]);
            }
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function filterVisibleCityColumns(Request $request, City $city): array
    {
        $payload = [
            'id' => $city->id,
            'name' => $city->name,
            'governorate_id' => $city->governorate_id,
            'created_at' => $city->created_at,
            'updated_at' => $city->updated_at,
        ];

        foreach (AreaPlanPermissionMap::CITY_VIEW_COLUMNS as $column => $permission) {
            if ($permission && ! $request->user()?->can($permission)) {
                unset($payload[$column]);
            }
        }

        return $payload;
    }
}
