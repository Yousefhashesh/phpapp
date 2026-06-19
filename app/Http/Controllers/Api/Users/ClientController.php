<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Support\Permissions\AccountPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Change client plan (plan_id).
     */
    public function changePlan(Request $request, Client $client): JsonResponse
    {
        $this->authorizePermission($request, 'client.update');
        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ]);
        $client->plan_id = $data['plan_id'];
        $client->save();
        return response()->json([
            'message' => 'Client plan updated.',
            'plan_id' => $client->plan_id,
        ]);
    }
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client.page');
        $this->authorizePermission($request, 'client.view');


        $query = Client::query()
            ->with(['user:id,name,username,phone', 'plan', 'shippingContent']);

        if ($request->user()?->hasRole('client')) {
            $query->where('user_id', $request->user()->id);
        }

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
            if ($type === 'settlement') {
                $requireShipperCollectionFirst = true;
                $settingValue = \App\Models\Setting::query()->where('key', 'require_shipper_collection_first')->value('value');
                if ($settingValue !== null) {
                    $normalized = strtolower(trim((string) $settingValue));
                    $requireShipperCollectionFirst = in_array($normalized, ['1', 'true', 'yes', 'on'], true) || $settingValue === true;
                }

                $eligibleStatuses = \App\Http\Controllers\Api\Orders\ClientSettlementController::ELIGIBLE_ORDER_STATUSES;

                $query->whereHas('orders', function ($sq) use ($eligibleStatuses) {
                    $sq->whereIn('status', $eligibleStatuses)
                        ->whereDoesntHave('clientSettlements', function ($ssq) {
                            $ssq->where('client_settlements.status', '!=', 'CANCELLED');
                        })
                        ->where('is_client_settled', false);
                });

                if ($requireShipperCollectionFirst) {
                    $query->where(function ($q) use ($eligibleStatuses) {
                        $q->where('can_settle_before_shipper_collected', true)
                            ->orWhereHas('orders', function ($sq) use ($eligibleStatuses) {
                                $sq->whereIn('status', $eligibleStatuses)
                                    ->whereDoesntHave('clientSettlements', function ($ssq) {
                                        $ssq->where('client_settlements.status', '!=', 'CANCELLED');
                                    })
                                    ->where('is_client_settled', false)
                                    ->where('is_shipper_collected', true);
                            });
                    });
                }
            } elseif ($type === 'return') {
                $query->whereHas('orders', function ($q) {
                    $q->eligibleForClientReturn();
                });
            }
        }

        $perPage = $request->get('per_page', $request->get('itemsPerPage', -1));
        $clients = $perPage == -1 
            ? $query->orderByDesc('id')->get()
            : $query->orderByDesc('id')->paginate($perPage);

        $data = $perPage == -1
            ? $clients->map(fn (Client $client): array => $this->filterVisibleColumns($request, $client))
            : collect($clients->items())->map(fn (Client $client): array => $this->filterVisibleColumns($request, $client));

        return response()->json([
            'data' => $data,
            'total' => $perPage == -1 ? $clients->count() : $clients->total(),
        ]);
    }

    public function show(Request $request, Client $client): JsonResponse
    {
        $this->authorizePermission($request, 'client.page');
        $this->authorizePermission($request, 'client.view');

        if ($request->user()?->hasRole('client') && (int) $client->user_id !== (int) $request->user()->id) {
            abort(403, 'You can only view your own client account.');
        }

        $client->load(['user:id,name,username,phone', 'plan', 'shippingContent']);

        return response()->json($this->filterVisibleColumns($request, $client));
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function filterVisibleColumns(Request $request, Client $client): array
    {
        $raw = $client->toArray();
        $result = [];

        foreach (AccountPermissionMap::CLIENT_VIEW_COLUMNS as $column => $permission) {
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
