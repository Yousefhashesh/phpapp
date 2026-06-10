<?php

namespace App\Http\Controllers\Api\Plan;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Support\Permissions\AreaPlanPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'plan.page');
        $this->authorizePermission($request, 'plan.view');

        $plans = Plan::query()
            ->with(['prices.governorate:id,name'])
            ->orderBy('name')
            ->get();

        return response()->json(
            $plans->map(fn (Plan $plan): array => $this->filterVisiblePlanColumns($request, $plan))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'plan.create');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:plans,name'],
            'order_count' => ['required', 'integer', 'min:0'],
            'prices' => ['nullable', 'array'],
            'prices.*.governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            'prices.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data), AreaPlanPermissionMap::PLAN_EDIT_COLUMNS);
        if (array_key_exists('prices', $data)) {
            $this->authorizeEditableColumns($request, ['governorate_id', 'price'], AreaPlanPermissionMap::PLAN_PRICE_EDIT_COLUMNS);
        }

        // Reject duplicate governorate_id entries in same request
        if (! empty($data['prices'])) {
            $govIds = array_column($data['prices'], 'governorate_id');
            abort_if(
                count($govIds) !== count(array_unique($govIds)),
                422,
                'Duplicate governorate entries in prices.'
            );
        }

        $plan = Plan::query()->create([
            'name' => $data['name'],
            'order_count' => $data['order_count'],
        ]);

        if (! empty($data['prices'])) {
            $rows = array_map(
                fn (array $p): array => [
                    'plan_id' => $plan->id,
                    'governorate_id' => $p['governorate_id'],
                    'price' => $p['price'],
                ],
                $data['prices']
            );
            PlanPrice::query()->insert($rows);
        }

        $plan->load('prices.governorate:id,name');

        return response()->json([
            'message' => 'Plan created successfully.',
            'data' => $this->filterVisiblePlanColumns($request, $plan),
        ], 201);
    }

    public function show(Request $request, Plan $plan): JsonResponse
    {
        $this->authorizePermission($request, 'plan.page');
        $this->authorizePermission($request, 'plan.view');

        $plan->load('prices.governorate:id,name');

        return response()->json($this->filterVisiblePlanColumns($request, $plan));
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $this->authorizePermission($request, 'plan.update');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('plans', 'name')->ignore($plan->id)],
            'order_count' => ['required', 'integer', 'min:0'],
            'prices' => ['nullable', 'array'],
            'prices.*.governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            'prices.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data), AreaPlanPermissionMap::PLAN_EDIT_COLUMNS);
        if (array_key_exists('prices', $data)) {
            $this->authorizeEditableColumns($request, ['governorate_id', 'price'], AreaPlanPermissionMap::PLAN_PRICE_EDIT_COLUMNS);
        }

        if (! empty($data['prices'])) {
            $govIds = array_column($data['prices'], 'governorate_id');
            abort_if(
                count($govIds) !== count(array_unique($govIds)),
                422,
                'Duplicate governorate entries in prices.'
            );
        }

        $plan->update([
            'name' => $data['name'],
            'order_count' => $data['order_count'],
        ]);

        if (array_key_exists('prices', $data)) {
            // Sync: delete existing and re-insert
            $plan->prices()->delete();

            if (! empty($data['prices'])) {
                $rows = array_map(
                    fn (array $p): array => [
                        'plan_id' => $plan->id,
                        'governorate_id' => $p['governorate_id'],
                        'price' => $p['price'],
                    ],
                    $data['prices']
                );
                PlanPrice::query()->insert($rows);
            }
        }

        $plan->load('prices.governorate:id,name');

        return response()->json([
            'message' => 'Plan updated successfully.',
            'data' => $this->filterVisiblePlanColumns($request, $plan),
        ]);
    }

    public function destroy(Request $request, Plan $plan): JsonResponse
    {
        $this->authorizePermission($request, 'plan.delete');

        $plan->delete();

        return response()->json(['message' => 'Plan deleted successfully.']);
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
    private function filterVisiblePlanColumns(Request $request, Plan $plan): array
    {
        $plan->loadMissing('prices.governorate:id,name');

        $payload = [
            'id' => $plan->id,
            'name' => $plan->name,
            'order_count' => $plan->order_count,
            'prices' => $plan->prices
                ->map(fn (PlanPrice $price): array => $this->filterVisiblePlanPriceColumns($request, $price))
                ->values()
                ->all(),
            'created_at' => $plan->created_at,
            'updated_at' => $plan->updated_at,
        ];

        foreach (AreaPlanPermissionMap::PLAN_VIEW_COLUMNS as $column => $permission) {
            if ($permission && ! $request->user()?->can($permission)) {
                unset($payload[$column]);
            }
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function filterVisiblePlanPriceColumns(Request $request, PlanPrice $planPrice): array
    {
        $planPrice->loadMissing('governorate:id,name');

        $payload = [
            'id' => $planPrice->id,
            'plan_id' => $planPrice->plan_id,
            'governorate_id' => $planPrice->governorate_id,
            'price' => $planPrice->price,
            'governorate' => $planPrice->governorate ? [
                'id' => $planPrice->governorate->id,
                'name' => $planPrice->governorate->name,
            ] : null,
            'created_at' => $planPrice->created_at,
            'updated_at' => $planPrice->updated_at,
        ];

        foreach (AreaPlanPermissionMap::PLAN_PRICE_VIEW_COLUMNS as $column => $permission) {
            if ($permission && ! $request->user()?->can($permission)) {
                unset($payload[$column]);
            }
        }

        return $payload;
    }
}
