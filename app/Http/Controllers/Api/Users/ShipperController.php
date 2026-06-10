<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Shipper;
use App\Support\Permissions\AccountPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipperController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'shipper.page');
        $this->authorizePermission($request, 'shipper.view');


        $query = Shipper::query()
            ->with(['user:id,name,username,phone']);

        if ($request->filled('q')) {
            $search = (string) $request->get('q');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                  ->orWhere('username', 'like', '%'.$search.'%')
                  ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('eligible_for')) {
            $type = $request->get('eligible_for');
            if ($type === 'collection') {
                $query->whereHas('orders', function ($q) {
                    $q->whereIn('status', \App\Http\Controllers\Api\Orders\ShipperCollectionController::ELIGIBLE_ORDER_STATUSES)
                      ->where('approval_status', 'APPROVED')
                      ->whereDoesntHave('shipperCollections', function ($sq) {
                          $sq->where('shipper_collections.status', '!=', 'CANCELLED');
                      })
                      ->where('is_shipper_collected', false)
                      ->where(function ($qq) {
                          $qq->where('total_amount', '>', 0)
                            ->orWhere('company_amount', '>', 0)
                            ->orWhere('shipping_fee', '>', 0);
                      });
                });
            } elseif ($type === 'return') {
                $query->whereHas('orders', function ($q) {
                    $q->whereIn('status', \App\Http\Controllers\Api\Orders\ShipperReturnController::ELIGIBLE_ORDER_STATUSES)
                      ->whereDoesntHave('shipperReturns', function ($sq) {
                          $sq->where('shipper_returns.status', '!=', 'CANCELLED');
                      })
                      ->where('is_shipper_returned', false);
                });
            }
        }

        $perPage = $request->get('per_page', 15);
        $shippers = $perPage == -1 
            ? $query->orderByDesc('id')->get()
            : $query->orderByDesc('id')->paginate($perPage);

        $data = $perPage == -1
            ? $shippers->map(fn (Shipper $shipper): array => $this->filterVisibleColumns($request, $shipper))
            : collect($shippers->items())->map(fn (Shipper $shipper): array => $this->filterVisibleColumns($request, $shipper));

        return response()->json([
            'data' => $data,
            'total' => $perPage == -1 ? $shippers->count() : $shippers->total(),
        ]);
    }

    public function show(Request $request, Shipper $shipper): JsonResponse
    {
        $this->authorizePermission($request, 'shipper.page');
        $this->authorizePermission($request, 'shipper.view');

        $shipper->load(['user:id,name,username,phone']);

        return response()->json($this->filterVisibleColumns($request, $shipper));
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function filterVisibleColumns(Request $request, Shipper $shipper): array
    {
        $raw = $shipper->toArray();
        $result = [];

        foreach (AccountPermissionMap::SHIPPER_VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $raw)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $raw[$column];
            }
        }

        return $result;
    }
}
