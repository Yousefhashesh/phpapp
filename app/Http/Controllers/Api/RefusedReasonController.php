<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefusedReason;
use App\Support\Permissions\RefusedReasonPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RefusedReasonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'refused-reason.page');
        $this->authorizePermission($request, 'refused-reason.view');

        $refusedReasons = RefusedReason::query()
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $refusedReasons->map(fn (RefusedReason $refusedReason): array => $this->filterVisibleColumns($request, $refusedReason))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'refused-reason.create');

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255', 'unique:refused_reasons,reason'],
            'status' => ['required', Rule::in(['OUT_FOR_DELIVERY', 'DELIVERED', 'HOLD', 'UNDELIVERED'])],
            'is_active' => ['nullable', 'boolean'],
            'is_clear' => ['nullable', 'boolean'],
            'is_edit_amount' => ['nullable', 'boolean'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        $refusedReason = RefusedReason::query()->create($data);

        return response()->json([
            'message' => 'Refused reason created successfully.',
            'data' => $this->filterVisibleColumns($request, $refusedReason),
        ], 201);
    }

    public function show(Request $request, RefusedReason $refusedReason): JsonResponse
    {
        $this->authorizePermission($request, 'refused-reason.page');
        $this->authorizePermission($request, 'refused-reason.view');

        return response()->json($this->filterVisibleColumns($request, $refusedReason));
    }

    public function update(Request $request, RefusedReason $refusedReason): JsonResponse
    {
        $this->authorizePermission($request, 'refused-reason.update');

        $data = $request->validate([
            'reason' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('refused_reasons', 'reason')->ignore($refusedReason->id)],
            'status' => ['sometimes', 'required', Rule::in(['OUT_FOR_DELIVERY', 'DELIVERED', 'HOLD', 'UNDELIVERED'])],
            'is_active' => ['nullable', 'boolean'],
            'is_clear' => ['nullable', 'boolean'],
            'is_edit_amount' => ['nullable', 'boolean'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        $refusedReason->update($data);

        return response()->json([
            'message' => 'Refused reason updated successfully.',
            'data' => $this->filterVisibleColumns($request, $refusedReason),
        ]);
    }

    public function destroy(Request $request, RefusedReason $refusedReason): JsonResponse
    {
        $this->authorizePermission($request, 'refused-reason.delete');

        $refusedReason->delete();

        return response()->json([
            'message' => 'Refused reason deleted successfully.',
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function authorizeEditableColumns(Request $request, array $columns): void
    {
        foreach ($columns as $column) {
            $permission = RefusedReasonPermissionMap::EDIT_COLUMNS[$column] ?? null;

            if ($permission && ! $request->user()?->can($permission)) {
                abort(403, "Missing permission: {$permission}");
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function filterVisibleColumns(Request $request, RefusedReason $refusedReason): array
    {
        $payload = $refusedReason->toArray();
        $result = [];

        foreach (RefusedReasonPermissionMap::VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $payload[$column];
            }
        }

        if (! array_key_exists('id', $result)) {
            $result['id'] = $refusedReason->id;
        }

        return $result;
    }
}
