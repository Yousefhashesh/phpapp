<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\Permissions\ActivityLogPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'activity-log.page');
        $this->authorizePermission($request, 'activity-log.view');

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'itemsPerPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer'],
            'entity_type' => ['nullable', 'string', 'max:100'],
            'entity_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $perPage = $validated['per_page'] ?? $validated['itemsPerPage'] ?? 25;

        $query = ActivityLog::query()
            ->with([
                'user:id,name,username',
                'loginSession:id,ip_address,country,city',
            ])
            ->when($request->filled('user_id'), function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            })
            ->when($request->filled('entity_type'), function ($q) use ($request) {
                $q->where('entity_type', $request->entity_type);
            })
            ->when($request->filled('entity_id'), function ($q) use ($request) {
                $q->where('entity_id', $request->entity_id);
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $search = '%'.$request->q.'%';
                $q->where(function ($inner) use ($search) {
                    $inner
                        ->where('action', 'like', $search)
                        ->orWhere('label', 'like', $search)
                        ->orWhere('entity_type', 'like', $search);
                });
            })
            ->orderByDesc('id');

        $logs = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => collect($logs->items())->map(fn (ActivityLog $log): array => $this->filterVisibleColumns($request, $log)),
            'total' => $logs->total(),
            'current_page' => $logs->currentPage(),
            'per_page' => $logs->perPage(),
            'last_page' => $logs->lastPage(),
        ]);
    }

    public function show(Request $request, ActivityLog $activityLog): JsonResponse
    {
        $this->authorizePermission($request, 'activity-log.page');
        $this->authorizePermission($request, 'activity-log.view');

        $activityLog->load([
            'user:id,name,username,phone',
            'loginSession:id,ip_address,country,city,device_name',
        ]);

        return response()->json($this->filterVisibleColumns($request, $activityLog));
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function filterVisibleColumns(Request $request, ActivityLog $log): array
    {
        $payload = $log->toArray();
        $result = [];

        foreach (ActivityLogPermissionMap::VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $payload[$column];
            }
        }

        if (! array_key_exists('id', $result)) {
            $result['id'] = $log->id;
        }

        return $result;
    }
}
