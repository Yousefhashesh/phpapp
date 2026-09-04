<?php

namespace App\Http\Controllers\Api\Orders;

use App\Exports\OrdersExport;
use App\Exports\OrdersTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\OrdersImport;
use App\Models\Client;
use App\Models\ClientSettlement;
use App\Models\ClientSettlementOrder;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\PlanPrice;
use App\Models\RefusedReason;
use App\Models\Shipper;
use App\Models\ShipperCollection;
use App\Models\ShipperCollectionOrder;
use App\Support\Permissions\OrdersPermissionMap;
use App\Support\Services\FinancialFormulaService;
use App\Traits\ChecksWorkingHours;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{
    use ChecksWorkingHours;

    private const FINAL_STATUSES = ['DELIVERED', 'UNDELIVERED'];

    private const FINANCIAL_DOCUMENT_INLINE_FIELDS = [
        'receiver_name',
        'phone',
        'total_amount',
        'shipping_fee',
        'commission_amount',
        'company_amount',
        'cod_amount',
        'order_note',
        'latest_status_note',
    ];

    private const STATUS_LABELS = [
        'OUT_FOR_DELIVERY' => 'Out for delivery',
        'DELIVERED' => 'Delivered',
        'HOLD' => 'On hold',
        'UNDELIVERED' => 'Undelivered',
    ];

    private const SUMMABLE_COLUMNS = [
        'total_amount',
        'shipping_fee',
        'commission_amount',
        'company_amount',
        'cod_amount',
    ];

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.page');
        $this->authorizePermission($request, 'order.view');

        $result = $this->getOrdersWithTotals($request);

        return response()->json($result);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.create');
        $this->checkWorkingHours('orders');

        if ($request->user()?->hasRole('client')) {
            $request->merge([
                'client_user_id' => $request->user()->id,
                'status' => 'OUT_FOR_DELIVERY',
            ]);
        }

        $data = $request->validate([
            'code' => ['nullable', 'string', 'unique:orders,code'],
            'external_code' => ['nullable', 'string'],
            'receiver_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'phone_2' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string'],
            'governorate_id' => ['required', 'exists:governorates,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'total_amount' => ['required', 'numeric',  ],
            'status' => ['required', Rule::in(['OUT_FOR_DELIVERY', 'DELIVERED', 'HOLD', 'UNDELIVERED'])],
            'shipper_user_id' => ['nullable', 'exists:users,id'],
            'client_user_id' => ['required', 'exists:users,id'],
            'shipping_content_id' => ['nullable', 'integer', 'exists:content,id'],
            'allow_open' => ['nullable', 'boolean'],
            'order_note' => ['nullable', 'string'],
            // 'shipping_fee'=>['nullable', 'numeric',  ]
        ]);

      //  $this->authorizeEditableColumns($request, array_keys($data));

        // $this->authorizeClientShipperMatchesGovernorate($request, $data);

        $data = $this->resolveDefaultShipper($data);
        $data = $this->applyAutomaticFinancials($data);
        $data['created_by'] = $request->user()->id;

        if (! empty($data['shipper_user_id'])) {
            $data['shipper_date'] = now()->toDateString();
        }

        if (empty($data['code'])) {
            $data['code'] = Order::generateUniqueCode();
        }

        $order = Order::query()->create($data);

        return response()->json([
            'message' => 'Order created successfully.',
            'data' => $this->filterVisibleColumns($request, $order),
        ], 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.page');
        $this->authorizePermission($request, 'order.view');
        $this->authorizeOrderVisible($request, $order);

        $validated = $request->validate([
            'include_history' => ['nullable', 'boolean'],
            'history_per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $order->load(['governorate:id,name', 'city:id,name', 'shipper:id,name', 'client:id,name']);

        $payload = $this->filterVisibleColumns($request, $order);

        if (($validated['include_history'] ?? false) === true) {
            $historyPerPage = $validated['history_per_page'] ?? 20;

            if (! $request->user()?->can('activity-log.view')) {
                $payload['history'] = $order->history()
                    ->where(function (Builder|QueryBuilder $query): void {
                        $query
                            ->where('action', 'created')
                            ->orWhereNotNull('new_values->status')
                            ->orWhereNotNull('old_values->status');
                    })
                    ->paginate($historyPerPage, ['*'], 'history_page')
                    ->appends($request->query())
                    ->through(fn ($log): array => $this->buildClientTimelineEntry($log))
                    ->toArray();
            } else {
                $payload['history'] = $order->history()
                    ->with([
                        'user:id,name,username,phone',
                        'loginSession:id,ip_address,country,city,device_name',
                    ])
                    ->paginate($historyPerPage, ['*'], 'history_page')
                    ->appends($request->query())
                    ->toArray();
            }
        }

        return response()->json($payload);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.update');
        $this->authorizeOrderVisible($request, $order);
        $this->authorizeFinalStatusUpdate($request, $order);

        // $data = $request->validate([
        //     'external_code' => ['sometimes', 'nullable', 'string'],
        //     'receiver_name' => ['sometimes', 'required', 'string', 'max:255'],
        //     'phone' => ['sometimes', 'required', 'string', 'max:30'],
        //     'phone_2' => ['sometimes', 'nullable', 'string', 'max:30'],
        //     'address' => ['sometimes', 'required', 'string'],
        //     'governorate_id' => ['sometimes', 'required', 'exists:governorates,id'],
        //     'city_id' => ['sometimes', 'required', 'exists:cities,id'],
        //     'shipper_user_id' => ['sometimes', 'nullable', 'exists:users,id'],
        //     'shipping_content_id' => ['sometimes', 'nullable', 'integer', 'exists:content,id'],
        //     'total_amount' => ['sometimes', 'required', 'numeric',  ],
        //     'status' => ['sometimes', 'required', Rule::in(['OUT_FOR_DELIVERY', 'DELIVERED', 'HOLD', 'UNDELIVERED'])],
        //     'allow_open' => ['sometimes', 'boolean'],
        //     'latest_status_note' => ['nullable', 'string'],
        //     'order_note' => ['nullable', 'string'],
        // ]);
        $data = $request->validate([
            'external_code' => ['sometimes', 'nullable', 'string'],
            'receiver_name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:30'],
            'phone_2' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'required', 'string'],
            'governorate_id' => ['sometimes', 'required', 'exists:governorates,id'],
            'city_id' => ['sometimes', 'required', 'exists:cities,id'],
            'shipper_user_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'shipping_content_id' => ['sometimes', 'nullable', 'integer', 'exists:content,id'],
            'total_amount' => ['sometimes', 'required', 'numeric',  ],
            'shipping_fee' => ['sometimes', 'nullable', 'numeric',  ],
            'commission_amount' => ['sometimes', 'nullable', 'numeric',  ],
            'company_amount' => ['sometimes', 'nullable', 'numeric',  ],
            'cod_amount' => ['sometimes', 'nullable', 'numeric',  ],
            'status' => ['sometimes', 'required', Rule::in(['OUT_FOR_DELIVERY', 'DELIVERED', 'HOLD', 'UNDELIVERED'])],
            'allow_open' => ['sometimes', 'boolean'],
            'latest_status_note' => ['nullable', 'string'],
            'order_note' => ['nullable', 'string'],
        ]);
        $this->authorizeEditableColumns($request, array_keys($data));
        $this->authorizeFinancialDocumentOrderUpdate($order, $data);

        if (array_key_exists('shipper_user_id', $data)) {
            $this->authorizeShipperChangeAllowed($order);
        }

        if (array_key_exists('status', $data)) {
            $this->authorizeRevertToActiveDeliveryStatus($order, $data['status']);
        }

        //  $this->authorizeClientShipperMatchesGovernorate($request, $data, $order);

        $this->authorizePriceEditOnFinalStatus($order, $data);

        $data = $this->resolveDefaultShipper($data, $order);

        if ($this->shouldRecalculateFinancials($data)) {
            $data = $this->applyAutomaticFinancials($data, $order);
        }

        if (array_key_exists('shipper_user_id', $data)) {
            $data['shipper_date'] = $data['shipper_user_id'] ? now()->toDateString() : null;
        }

        $order->update($data);

        // Recalculate related financial document totals if financial fields changed
        $financialFields = ['total_amount', 'shipping_fee', 'commission_amount', 'company_amount', 'cod_amount'];
        if (array_intersect(array_keys($data), $financialFields) !== []) {
            $this->recalculateRelatedFinancialDocuments($order->fresh());
        }

        return response()->json([
            'message' => 'Order updated successfully.',
            'data' => $this->filterVisibleColumns($request, $order),
        ]);
    }

    public function destroy(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.delete');
        $this->authorizeOrderVisible($request, $order);
        $this->authorizeNotShipperCollected($order);

        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully.',
        ]);
    }

    public function restore(Request $request, $id): JsonResponse
    {
        $this->authorizePermission($request, 'order.delete');
        $order = Order::onlyTrashed()->forUserRole()->findOrFail($id);
        $order->restore();

        return response()->json([
            'message' => 'Order restored successfully.',
        ]);
    }

    public function forceDelete(Request $request, $id): JsonResponse
    {
        $this->authorizePermission($request, 'order.delete');
        $order = Order::onlyTrashed()->forUserRole()->findOrFail($id);
        $order->forceDelete();

        return response()->json([
            'message' => 'Order permanently deleted.',
        ]);
    }

    // =========================================================================
    // Workflow & Specialized Actions
    // =========================================================================

    public function changeStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.change-status');
        $this->authorizeOrderVisible($request, $order);
        $this->authorizeNotShipperCollected($order);
        $this->authorizeFinalStatusUpdate($request, $order);

        $data = $request->validate([
            'status' => ['required', Rule::in(['OUT_FOR_DELIVERY', 'DELIVERED', 'HOLD', 'UNDELIVERED'])],
            'reason' => ['nullable', 'string'],
            'refused_reason_ids' => ['nullable', 'array'],
            'refused_reason_ids.*' => ['integer', 'exists:refused_reasons,id'],
            'total_amount' => ['sometimes', 'nullable', 'numeric',  ],
            'has_return' => ['nullable', 'boolean'],
        ]);

        $this->authorizeRevertToActiveDeliveryStatus($order, $data['status']);

        $reasonIds = $data['refused_reason_ids'] ?? [];
        $refusedReasons = collect($reasonIds)
            ->map(function ($id) {
                return RefusedReason::query()->find($id);
            })
            ->filter();

        $allowsEditAmount = $refusedReasons->contains('is_edit_amount', true);
        $isClear = $refusedReasons->contains('is_clear', true);

        $noteParts = [];
        foreach ($refusedReasons as $rr) {
            $noteParts[] = $rr->reason;
        }
        if (isset($data['reason']) && trim($data['reason']) !== '') {
            $noteParts[] = $data['reason'];
        }

        $latestNote = implode(', ', $noteParts);

        $payload = [
            'status' => $data['status'],
            'latest_status_note' => $latestNote,
        ];

        if ($isClear) {
            $payload['total_amount'] = 0;
            $payload['shipping_fee'] = 0;
            $payload['commission_amount'] = 0;
            $payload['company_amount'] = 0;
            $payload['cod_amount'] = 0;
        }

        if (array_key_exists('has_return', $data)) {
            $payload['has_return'] = (bool) $data['has_return'];
            if ($payload['has_return']) {
                $payload['has_return_at'] = now();
            } else {
                $payload['has_return_at'] = null;
            }
        } elseif ($data['status'] === 'DELIVERED' && $allowsEditAmount) {
            $payload['has_return'] = true;
            $payload['has_return_at'] = now();
        } elseif ($data['status'] === 'UNDELIVERED') {
          //  $payload['has_return'] = true;
            $payload['has_return_at'] = now();
        }

        if (! $isClear) {
            if (! $allowsEditAmount && array_key_exists('total_amount', $data)) {
                throw ValidationException::withMessages([
                    'total_amount' => ['total_amount can only be edited when one of the selected reasons allows amount edit.'],
                ]);
            }

            if ($allowsEditAmount && array_key_exists('total_amount', $data)) {
                $payload['total_amount'] = $data['total_amount'];
                $payload = $this->applyAutomaticFinancials($payload, $order);
            }
        }

        $this->authorizeEditableColumns($request, array_keys($payload));

        $order->update($payload);
        $order->refusedReasons()->sync(array_unique($reasonIds));

        return response()->json([
            'message' => 'Order status updated successfully.',
            'data' => $this->filterVisibleColumns($request, $order),
        ]);
    }

    public function changeShipper(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.change-shipper');
        $this->authorizeOrderVisible($request, $order);
        $this->authorizeNotShipperCollected($order);
        $this->authorizeShipperChangeAllowed($order);
        $this->authorizeFinalStatusUpdate($request, $order);

        $data = $request->validate([
            'shipper_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'commission_amount' => ['nullable', 'numeric',  ],
            'shipper_date' => ['nullable', 'date'],
        ]);

        $payload = [
            'shipper_user_id' => $data['shipper_user_id'] ?? null,
        ];

        $payload = $this->resolveDefaultShipper($payload, $order);
        //  $this->authorizeClientShipperMatchesGovernorate($request, $payload, $order);

        if (array_key_exists('shipper_date', $data) && $data['shipper_date']) {
            $payload['shipper_date'] = $data['shipper_date'];
        } else {
            $payload['shipper_date'] = $payload['shipper_user_id'] ? now()->toDateString() : null;
        }

        if (array_key_exists('commission_amount', $data) && $data['commission_amount'] !== null && $data['commission_amount'] !== '') {
            $payload['commission_amount'] = $data['commission_amount'];
            $payload['company_amount'] = ($order->total_amount ?? 0) - $payload['commission_amount'];
        }

        $payload = $this->applyAutomaticFinancials($payload, $order);

        $order->update($payload);

        return response()->json([
            'message' => 'Shipper updated successfully.',
            'data' => $this->filterVisibleColumns($request, $order),
        ]);
    }

    public function changeNote(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.change-note');
        $this->authorizeOrderVisible($request, $order);
        $this->authorizeNotShipperCollected($order);
        $this->authorizeFinalStatusUpdate($request, $order);

        $data = $request->validate([
            'order_note' => ['required', 'nullable', 'string'],
        ]);

        $order->update(['order_note' => $data['order_note']]);

        return response()->json([
            'message' => 'Order note updated successfully.',
            'data' => $this->filterVisibleColumns($request, $order),
        ]);
    }

    public function approve(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.approve');
        $this->authorizeOrderVisible($request, $order);

        $order = $this->markOrderApproved($request, $order);

        return response()->json([
            'message' => 'Order approved successfully.',
            'data' => $this->filterVisibleColumns($request, $order),
        ]);
    }

    public function reject(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.reject');
        $this->authorizeOrderVisible($request, $order);

        $data = $request->validate([
            'approval_note' => ['nullable', 'string'],
        ]);

        $order->update([
            'approval_status' => 'REJECTED',
            'rejected_at' => now(),
            'rejected_by' => $request->user()->id,
            'approval_note' => $data['approval_note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Order rejected successfully.',
            'data' => $this->filterVisibleColumns($request, $order),
        ]);
    }

    public function changeExternalCode(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.change-external-code');
        $this->authorizeOrderVisible($request, $order);
        $this->authorizeNotShipperCollected($order);

        if (in_array($order->status, self::FINAL_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => ['External code cannot be changed when order status is DELIVERED or UNDELIVERED.'],
            ]);
        }

        $data = $request->validate([
            'external_code' => ['required', 'nullable', 'string', 'max:255'],
        ]);

        $this->authorizeEditableColumns($request, ['external_code']);

        $order->update(['external_code' => $data['external_code']]);

        return response()->json([
            'message' => 'Order external code updated successfully.',
            'data' => $this->filterVisibleColumns($request, $order),
        ]);
    }

    // =========================================================================
    // Bulk Operations
    // =========================================================================

    public function bulkChangeShipper(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.change-shipper');

        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'exists:orders,id'],
            'shipper_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'commission_amount' => ['nullable', 'numeric',  ],
            'shipper_date' => ['nullable', 'date'],
        ]);

        $orders = Order::query()->forUserRole()->whereIn('id', $data['order_ids'])->get();

        $updated = DB::transaction(function () use ($orders, $data): array {
            $result = [];

            foreach ($orders as $order) {
                if (! $order instanceof Order) {
                    continue;
                }
                $this->authorizeNotShipperCollected($order);
                $this->authorizeShipperChangeAllowed($order);
                $this->authorizeFinalStatusUpdate(request(), $order);

                $payload = [
                    'shipper_user_id' => $data['shipper_user_id'] ?? null,
                ];

                $payload = $this->resolveDefaultShipper($payload, $order);
                //  $this->authorizeClientShipperMatchesGovernorate(request(), $payload, $order);

                if (array_key_exists('shipper_date', $data) && $data['shipper_date']) {
                    $payload['shipper_date'] = $data['shipper_date'];
                } else {
                    $payload['shipper_date'] = $payload['shipper_user_id'] ? now()->toDateString() : null;
                }

                if (array_key_exists('commission_amount', $data) && $data['commission_amount'] !== null && $data['commission_amount'] !== '') {
                    $payload['commission_amount'] = $data['commission_amount'];
                    $payload['company_amount'] = ($order->total_amount ?? 0) - $payload['commission_amount'];
                }

                $payload = $this->applyAutomaticFinancials($payload, $order);

                $order->update($payload);
                $result[] = $order->id;
            }

            return $result;
        });

        return response()->json([
            'message' => 'Shipper updated successfully for selected orders.',
            'updated_order_ids' => $updated,
        ]);
    }

    public function bulkChangeStatus(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.change-status');

        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'exists:orders,id'],
            'status' => ['required', Rule::in(['OUT_FOR_DELIVERY', 'DELIVERED', 'HOLD', 'UNDELIVERED'])],
            'reason' => ['nullable', 'string'],
            'refused_reason_id' => ['nullable', 'integer', 'exists:refused_reasons,id'],
            'refused_reason_ids' => ['nullable', 'array'],
            'refused_reason_ids.*' => ['integer', 'exists:refused_reasons,id'],
            'total_amount' => ['sometimes', 'nullable', 'numeric',  ],
        ]);

        $reasonIds = $data['refused_reason_ids'] ?? (isset($data['refused_reason_id']) ? [$data['refused_reason_id']] : []);
        $refusedReasons = collect($reasonIds)->map(fn ($id) => RefusedReason::find($id))->filter();

        $allowsEditAmount = $refusedReasons->contains('is_edit_amount', true);
        $isClear = $refusedReasons->contains('is_clear', true);

        if (! $allowsEditAmount && array_key_exists('total_amount', $data)) {
            throw ValidationException::withMessages([
                'total_amount' => ['total_amount can only be edited when one of the selected reasons allows amount edit.'],
            ]);
        }

        $orders = Order::query()->forUserRole()->whereIn('id', $data['order_ids'])->get();

        $updated = DB::transaction(function () use ($orders, $data, $refusedReasons, $allowsEditAmount, $isClear, $reasonIds): array {
            $result = [];

            foreach ($orders as $order) {
                if (! $order instanceof Order) {
                    continue;
                }

                $this->authorizeNotShipperCollected($order);
                $this->authorizeFinalStatusUpdate(request(), $order);
                $this->authorizeRevertToActiveDeliveryStatus($order, $data['status']);

                // Build note
                $noteParts = [];
                foreach ($refusedReasons as $rr) {
                    $noteParts[] = $rr->reason;
                }
                if (isset($data['reason']) && trim($data['reason']) !== '') {
                    $noteParts[] = $data['reason'];
                }
                $latestNote = implode(', ', $noteParts);

                $payload = [
                    'status' => $data['status'],
                    'latest_status_note' => $latestNote ?: null,
                ];

                if ($isClear) {
                    $payload = array_merge($payload, [
                        'total_amount' => 0,
                        'shipping_fee' => 0,
                        'commission_amount' => 0,
                        'company_amount' => 0,
                        'cod_amount' => 0,
                    ]);
                }

                if ($data['status'] === 'DELIVERED' && $allowsEditAmount) {
                    $payload['has_return'] = true;
                    $payload['has_return_at'] = now();
                } elseif ($data['status'] === 'UNDELIVERED') {
                    $payload['has_return'] = true;
                    $payload['has_return_at'] = now();
                }

                if (! $isClear && $allowsEditAmount && array_key_exists('total_amount', $data)) {
                    $payload['total_amount'] = $data['total_amount'];
                }

                $this->authorizeEditableColumns(request(), array_keys($payload));
                $this->authorizeNoPriceEditOnFinalStatus($order, $payload);

                $payload = $this->applyAutomaticFinancials($payload, $order);

                $order->update($payload);
                $order->refusedReasons()->sync($reasonIds);

                $result[] = $order->id;
            }

            return $result;
        });

        return response()->json([
            'message' => 'Status updated successfully for selected orders.',
            'updated_order_ids' => $updated,
        ]);
    }

    public function bulkApprove(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.approve');

        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $orders = Order::query()->forUserRole()->whereIn('id', $data['order_ids'])->get();

        $updated = DB::transaction(function () use ($orders, $request): array {
            $result = [];

            foreach ($orders as $order) {
                if (! $order instanceof Order) {
                    continue;
                }

                $this->markOrderApproved($request, $order);
                $result[] = $order->id;
            }

            return $result;
        });

        return response()->json([
            'message' => 'Status updated to APPROVED for selected orders.',
            'updated_order_ids' => $updated,
        ]);
    }

    public function bulkReject(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.reject');

        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'exists:orders,id'],
            'approval_note' => ['nullable', 'string'],
        ]);

        $orders = Order::query()->forUserRole()->whereIn('id', $data['order_ids'])->get();

        $updated = DB::transaction(function () use ($orders, $data, $request): array {
            $result = [];

            foreach ($orders as $order) {
                if (! $order instanceof Order) {
                    continue;
                }

                $order->update([
                    'approval_status' => 'REJECTED',
                    'rejected_at' => now(),
                    'rejected_by' => $request->user()->id,
                    'approval_note' => $data['approval_note'] ?? null,
                ]);
                $result[] = $order->id;
            }

            return $result;
        });

        return response()->json([
            'message' => 'Status updated to REJECTED for selected orders.',
            'updated_order_ids' => $updated,
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.delete');

        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $orders = Order::query()->forUserRole()->whereIn('id', $data['order_ids'])->get();

        $deletedCount = DB::transaction(function () use ($orders): int {
            $count = 0;
            foreach ($orders as $order) {
                if (! $order instanceof Order) {
                    continue;
                }

                $this->authorizeNotShipperCollected($order);
                $order->delete();
                $count++;
            }

            return $count;
        });

        return response()->json([
            'message' => "Order(s) deleted successfully. Total: {$deletedCount}",
        ]);
    }

    public function bulkRestore(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.delete');

        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer'],
        ]);

        $orders = Order::onlyTrashed()->forUserRole()->whereIn('id', $data['order_ids'])->get();

        $restoredCount = DB::transaction(function () use ($orders): int {
            $count = 0;
            foreach ($orders as $order) {
                $order->restore();
                $count++;
            }

            return $count;
        });

        return response()->json([
            'message' => "Order(s) restored successfully. Total: {$restoredCount}",
        ]);
    }

    public function bulkForceDelete(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.delete');

        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer'],
        ]);

        $orders = Order::onlyTrashed()->forUserRole()->whereIn('id', $data['order_ids'])->get();

        $deletedCount = DB::transaction(function () use ($orders): int {
            $count = 0;
            foreach ($orders as $order) {
                $order->forceDelete();
                $count++;
            }

            return $count;
        });

        return response()->json([
            'message' => "Order(s) permanently deleted. Total: {$deletedCount}",
        ]);
    }

    // =========================================================================
    // Order Visibility & Tools
    // =========================================================================

    public function myOrders(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.page');
        $this->authorizePermission($request, 'order.view');
        $this->authorizePermission($request, 'order.my-orders');

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $perPage = $validated['per_page'] ?? 100;

        $orders = Order::query()
            ->forUserRole()
            ->with(['governorate:id,name', 'city:id,name', 'shipper:id,name', 'client:id,name', 'shippingContent:id,name'])
            ->whereNotIn('status', self::FINAL_STATUSES)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->query())
            ->through(fn (Order $order): array => $this->formatMyOrderRow($order));

        return response()->json($orders);
    }

    public function scan(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.page');
        $this->authorizePermission($request, 'order.view');

        $data = $request->validate([
            'code' => ['nullable', 'string'],
            'external_code' => ['nullable', 'string'],
        ]);

        $code = trim((string) ($data['code'] ?? ''));
        $externalCode = trim((string) ($data['external_code'] ?? ''));

        if ($code === '' && $externalCode === '') {
            throw ValidationException::withMessages([
                'code' => ['Please provide code or external_code.'],
            ]);
        }

        $order = Order::query()
            ->with(['governorate:id,name', 'city:id,name', 'shipper:id,name', 'client:id,name', 'shippingContent:id,name'])
            ->where(function (Builder $query) use ($code, $externalCode): void {
                if ($code !== '') {
                    $query->where('code', $code);
                }

                if ($externalCode !== '') {
                    if ($code !== '') {
                        $query->orWhere('external_code', $externalCode);
                    } else {
                        $query->where('external_code', $externalCode);
                    }
                }
            })
            ->first();

        if (! $order) {
            throw ValidationException::withMessages([
                'code' => ['Order not found for provided code/external_code.'],
            ]);
        }

        $wasPending = $order->approval_status === 'PENDING';
        $order = $this->approveOrderOnScanIfEligible($request, $order);

        return response()->json([
            'message' => $wasPending && $order->approval_status === 'APPROVED'
                ? 'Order scanned and approved successfully.'
                : 'Order scanned successfully.',
            'data' => $this->filterVisibleColumns($request, $order),
        ]);
    }

    private function markOrderApproved(Request $request, Order $order): Order
    {
        $order->update([
            'approval_status' => 'APPROVED',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        return $order->fresh();
    }

    private function approveOrderOnScanIfEligible(Request $request, Order $order): Order
    {
        if ($order->approval_status !== 'PENDING') {
            return $order;
        }

        if (! $request->user()?->can('order.approve')) {
            return $order;
        }

        return $this->markOrderApproved($request, $order);
    }

    public function history(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.page');
        $this->authorizePermission($request, 'order.view');
        $this->authorizeOrderVisible($request, $order);

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $perPage = $validated['per_page'] ?? 50;

        if (! $request->user()?->can('activity-log.view')) {
            $timeline = $order->history()
                ->where(function (Builder|QueryBuilder $query): void {
                    $query
                        ->where('action', 'created')
                        ->orWhereNotNull('new_values->status')
                        ->orWhereNotNull('old_values->status');
                })
                ->paginate($perPage)
                ->appends($request->query())
                ->through(fn ($log): array => $this->buildClientTimelineEntry($log));

            return response()->json($timeline);
        }

        $history = $order->history()
            ->with([
                'user:id,name,username,phone',
                'loginSession:id,ip_address,country,city,device_name',
            ])
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json($history);
    }

    public function shippingLabel(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.page');
        $this->authorizePermission($request, 'order.view');
        $this->authorizeOrderVisible($request, $order);

        $order->load([
            'client:id,name,phone',
            'shipper:id,name,phone',
            'shippingContent:id,name',
            'governorate:id,name',
            'city:id,name',
        ]);

        $receiverPhones = array_values(array_filter([
            $order->phone,
            $order->phone_2,
        ], static fn ($phone): bool => $phone !== null && $phone !== ''));

        $formattedAddress = implode(' \\ ', array_values(array_filter([
            $order->governorate?->name,
            $order->city?->name,
            $order->address,
        ], static fn ($part): bool => $part !== null && $part !== '')));

        $labelCode = filled($order->external_code) ? $order->external_code : $order->code;

        return response()->json([
            'order_id' => $order->id,
            'code' => $order->code,
            'external_code' => $order->external_code,
            'label_code' => $labelCode,
            'status' => $order->status,
            'client_name' => $order->client?->name,
            'client_phone' => $order->client?->phone,
            'shipper_name' => $order->shipper?->name,
            'shipper_phone' => $order->shipper?->phone,
            'receiver_name' => $order->receiver_name,
            'receiver_phones' => $receiverPhones,
            'receiver_phones_text' => implode(' - ', $receiverPhones),
            'phone' => $order->phone,
            'phone_2' => $order->phone_2,
            'governorate_name' => $order->governorate?->name,
            'city_name' => $order->city?->name,
            'street_address' => $order->address,
            'address' => $formattedAddress,
            'total_amount' => $order->total_amount,
            'cod' => $order->cod_amount,
            'shipping_fee' => $order->shipping_fee,
            'commission_amount' => $order->commission_amount,
            'company_amount' => $order->company_amount,
            'allow_open' => (bool) $order->allow_open,
            'shipping_content' => $order->shippingContent?->name,
            'order_note' => $order->order_note,
            'latest_status_note' => $order->latest_status_note,
            'shipper_date' => $order->shipper_date,
            'created_at' => $order->created_at,
        ]);
    }

    // =========================================================================
    // Data & Import/Export
    // =========================================================================

    public function import(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.create');
        $this->checkWorkingHours('orders');

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,xls'],
        ]);

        $import = new OrdersImport($request->user()->id);
        Excel::import($import, $request->file('file'));

        $results = $import->getResults();

        return response()->json([
            'message' => 'Import completed.',
            'success_count' => $results['success_count'],
            'errors' => $results['errors'],
        ]);
    }

    public function export(Request $request)
    {
        $this->authorizePermission($request, 'order.export');

        $ids = $request->input('ids');
        if ($ids && is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if ($ids && is_array($ids)) {
            return Excel::download(new OrdersExport(null, null, $ids), 'orders_export.xlsx');
        }

        $query = Order::query();
        $this->applyOrderSearch($query, $request->all());

        return Excel::download(new OrdersExport($query), 'orders_filtered_export.xlsx');
    }

    public function downloadTemplate()
    {
        return Excel::download(new OrdersTemplateExport, 'orders_template.xlsx');
    }

    public function init(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.page');
        $this->authorizePermission($request, 'order.view');

        $clientQuery = Client::query()->with('user:id,name')->orderByDesc('id');
        if ($request->user()?->hasRole('client')) {
            $clientQuery->where('user_id', $request->user()->id);
        }

        $metadata = [
            'governorates' => Governorate::query()
                ->select('id', 'name', 'default_shipper_user_id')
                ->with(['cities:id,governorate_id,name', 'shippers:id,name'])
                ->get()
                ->map(function (Governorate $governorate): array {
                    return [
                        'id' => $governorate->id,
                        'name' => $governorate->name,
                        'default_shipper_user_id' => $governorate->default_shipper_user_id,
                        'shipper_user_ids' => $governorate->shippers->pluck('id')->values()->all(),
                        'cities' => $governorate->cities,
                    ];
                }),
            'shippers' => Shipper::query()->with('user:id,name')->orderByDesc('id')->get()->map(function ($s) {
                return [
                    'id' => $s->user_id,
                    'name' => $s->user?->name ?? 'Unknown',
                    'commission_rate' => $s->commission_rate,
                ];
            }),
            'clients' => $clientQuery->get()->map(function ($c) {
                return [
                    'id' => $c->user_id,
                    'name' => $c->user?->name ?? 'Unknown',
                    'plan_id' => $c->plan_id,
                    'shipping_content_id' => $c->shipping_content_id,
                    'shipping_fee' => $c->shipping_fee,
                ];
            }),
            'contents' => \App\Models\Content::query()->select('id', 'name')->get(),
            'plans' => \App\Models\Plan::query()->with('prices')->get(),
            'refused_reasons' => RefusedReason::query()->where('is_active', true)->get(),
            'statuses' => self::STATUS_LABELS,
            'working_hours' => \App\Models\Setting::query()->where('group', 'working_hours')->pluck('value', 'key')->all(),
        ];

        $orders = $this->getOrdersWithTotals($request);

        return response()->json([
            'metadata' => $metadata,
            'orders' => $orders,
        ]);
    }

    // =========================================================================
    // Private Helpers & Logic
    // =========================================================================

    private function getOrdersWithTotals(Request $request): array
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:255'],

            // Allow top-level filters for compatibility
            'code' => ['nullable', 'string', 'max:255'],
            'external_code' => ['nullable', 'string', 'max:255'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_2' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'string'], // Handles comma separated too
            'statuses' => ['nullable', 'array'],
            'approval_status' => ['nullable', 'string'],
            'approval_statuses' => ['nullable', 'array'],
            'governorate_id' => ['nullable', 'integer'],
            'city_id' => ['nullable', 'integer'],
            // 'shipper_user_id' => ['nullable', 'integer'],
            // 'client_user_id' => ['nullable', 'integer'],
            'shipper_user_id' => ['nullable', 'string', 'max:255'],
            'client_user_id' => ['nullable', 'string', 'max:255'],
            'allow_open' => ['nullable'],
            'collection_state' => ['nullable', 'string'],
            'is_shipper_collected' => ['nullable'],
            'is_client_settled' => ['nullable'],
            'is_shipper_returned' => ['nullable'],
            'is_client_returned' => ['nullable'],
            'has_return' => ['nullable'],
            'is_in_shipper_collection' => ['nullable'],
            'is_in_client_settlement' => ['nullable'],
            'is_in_shipper_return' => ['nullable'],
            'is_in_client_return' => ['nullable'],
            'shipper_return_pending' => ['nullable'],
            'client_return_pending' => ['nullable'],
            'order_note' => ['nullable', 'string', 'max:255'],
            'latest_status_note' => ['nullable', 'string', 'max:255'],
            'shipper_date' => ['nullable', 'string', 'max:255'],
            'shipping_fee' => ['nullable', 'string', 'max:255'],
            'commission_amount' => ['nullable', 'string', 'max:255'],
            'company_amount' => ['nullable', 'string', 'max:255'],
            'cod_amount' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'array'],
            'search.code' => ['nullable', 'string', 'max:255'],
            'search.external_code' => ['nullable', 'string', 'max:255'],
            'search.receiver_name' => ['nullable', 'string', 'max:255'],
            'search.phone' => ['nullable', 'string', 'max:30'],
            'search.phone_2' => ['nullable', 'string', 'max:30'],
            'search.address' => ['nullable', 'string', 'max:1000'],
            'search.status' => ['nullable', Rule::in(['OUT_FOR_DELIVERY', 'DELIVERED', 'HOLD', 'UNDELIVERED'])],
            'search.statuses' => ['nullable', 'array'],
            'search.statuses.*' => [Rule::in(['OUT_FOR_DELIVERY', 'DELIVERED', 'HOLD', 'UNDELIVERED'])],
            'search.approval_status' => ['nullable', Rule::in(['PENDING', 'APPROVED', 'REJECTED'])],
            'search.governorate_id' => ['nullable', 'integer', 'exists:governorates,id'],
            'search.city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'search.shipper_user_id' => ['nullable', 'string'],
            'search.client_user_id' => ['nullable', 'string'],
            'search.allow_open' => ['nullable', 'boolean'],
            'search.collection_state' => ['nullable', Rule::in(['not_collected', 'ready_to_collect', 'collected'])],
            'search.is_shipper_collected' => ['nullable', 'boolean'],
            'search.is_client_settled' => ['nullable', 'boolean'],
            'search.is_shipper_returned' => ['nullable', 'boolean'],
            'search.is_client_returned' => ['nullable', 'boolean'],
            'search.has_return' => ['nullable', 'boolean'],
            'search.is_in_shipper_collection' => ['nullable', 'boolean'],
            'search.is_in_client_settlement' => ['nullable', 'boolean'],
            'search.is_in_shipper_return' => ['nullable', 'boolean'],
            'search.is_in_client_return' => ['nullable', 'boolean'],
            'trashed' => ['nullable', 'string', Rule::in(['with', 'only'])],
        ]);

        $perPage = $validated['per_page'] ?? 100;

        $query = Order::query()
            ->forUserRole()
            ->with(['governorate:id,name', 'city:id,name', 'shipper:id,name', 'client:id,name', 'shippingContent:id,name', 'refusedReasons'])
            ->orderByDesc('id');

        if (isset($validated['trashed'])) {
            if ($validated['trashed'] === 'only') {
                $query->onlyTrashed();
            } elseif ($validated['trashed'] === 'with') {
                $query->withTrashed();
            }
        }

        $this->applyOrderSearch($query, $validated);

        $summaryQuery = Order::query()->forUserRole();

        if (isset($validated['trashed'])) {
            if ($validated['trashed'] === 'only') {
                $summaryQuery->onlyTrashed();
            } elseif ($validated['trashed'] === 'with') {
                $summaryQuery->withTrashed();
            }
        }

        $this->applyOrderSearch($summaryQuery, $validated);
        $totals = $summaryQuery->selectRaw('
                SUM(total_amount) as total_amount,
                SUM(shipping_fee) as shipping_fee,
                SUM(commission_amount) as commission_amount,
                SUM(company_amount) as company_amount,
                SUM(cod_amount) as cod_amount
            ')->first();

        $orders = $query
            ->paginate($perPage)
            ->appends($request->query())
            ->through(fn (Order $order): array => $this->filterVisibleColumns($request, $order));

        return [
            ...$orders->toArray(),
            'totals' => [
                'total_amount' => round((float) ($totals->total_amount ?? 0), 2),
                'shipping_fee' => round((float) ($totals->shipping_fee ?? 0), 2),
                'commission_amount' => round((float) ($totals->commission_amount ?? 0), 2),
                'company_amount' => round((float) ($totals->company_amount ?? 0), 2),
                'cod_amount' => round((float) ($totals->cod_amount ?? 0), 2),
            ],
        ];
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function authorizeOrderVisible(Request $request, Order $order): void
    {
        $user = $request->user();

        if (! $user || $user->hasAnyRole(['admin', 'super-admin'])) {
            return;
        }

        if ($user->hasRole('client') && (int) $order->client_user_id !== (int) $user->id) {
            abort(403, 'You can only access your own orders.');
        }

        if ($user->hasRole('shipper') && (int) $order->shipper_user_id !== (int) $user->id) {
            abort(403, 'You can only access assigned orders.');
        }
    }

    private function authorizeFinalStatusUpdate(Request $request, Order $order): void
    {
        if (! in_array($order->status, self::FINAL_STATUSES, true)) {
            return;
        }

        abort_unless(
            $request->user()?->can('order.update-after-final-status'),
            403,
            'Missing permission: order.update-after-final-status'
        );
    }

    private function authorizeNotShipperCollected(Order $order): void
    {
        if ((bool) $order->is_shipper_collected) {
            throw ValidationException::withMessages([
                'order' => ['Order is locked because it has already been collected by the shipper.'],
            ]);
        }
    }

    private function isFinancialDocumentInlineUpdate(array $data): bool
    {
        return array_diff(array_keys($data), self::FINANCIAL_DOCUMENT_INLINE_FIELDS) === [];
    }

    private function authorizeFinancialDocumentOrderUpdate(Order $order, array $data): void
    {
        if (! $order->isFinanciallyLockedForStatusRevert()) {
            $this->authorizeNotShipperCollected($order);

            return;
        }

        if (! $this->isFinancialDocumentInlineUpdate($data)) {
            throw ValidationException::withMessages([
                'order' => ['Cannot modify these fields while order is in an active collection, settlement, or return. Cancel/unlock the document first.'],
            ]);
        }
    }

    private function authorizePriceEditOnFinalStatus(Order $order, array $payload): void
    {
        if ($order->isFinanciallyLockedForStatusRevert() && $this->isFinancialDocumentInlineUpdate($payload)) {
            return;
        }

        $this->authorizeNoPriceEditOnFinalStatus($order, $payload);
    }

    private function authorizeRevertToActiveDeliveryStatus(Order $order, string $newStatus): void
    {
        if (! in_array($newStatus, ['OUT_FOR_DELIVERY', 'HOLD'], true)) {
            return;
        }

        if (! $order->isFinanciallyLockedForStatusRevert()) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => ['Cannot change order status to OUT_FOR_DELIVERY or HOLD while it is in an active shipper collection, client settlement, or return. Cancel/unlock the financial document first.'],
        ]);
    }

    private function authorizeShipperChangeAllowed(Order $order): void
    {
        if (in_array($order->status, self::FINAL_STATUSES, true)) {
            throw ValidationException::withMessages([
                'shipper_user_id' => ['Shipper cannot be changed when order status is DELIVERED or UNDELIVERED.'],
            ]);
        }
    }

    private function authorizeNoPriceEditOnFinalStatus(Order $order, array $payload): void
    {
        if (! in_array($order->status, self::FINAL_STATUSES, true)) {
            return;
        }

        $priceRelatedFields = [
            'total_amount',
            'shipping_fee',
            'commission_amount',
            'company_amount',
            'cod_amount',
            'governorate_id',
        ];

        foreach ($priceRelatedFields as $field) {
            if (array_key_exists($field, $payload)) {
                throw ValidationException::withMessages([
                    'total_amount' => ['Price fields cannot be changed when order status is DELIVERED or UNDELIVERED.'],
                ]);
            }
        }
    }

    private function resolveRefusedReason(string $status, ?int $refusedReasonId): ?RefusedReason
    {
        if ($refusedReasonId === null) {
            return null;
        }

        $refusedReason = RefusedReason::query()->find($refusedReasonId);

        if (! $refusedReason instanceof RefusedReason) {
            throw ValidationException::withMessages([
                'refused_reason_id' => ['Selected refused reason was not found.'],
            ]);
        }

        if (! $refusedReason->is_active) {
            throw ValidationException::withMessages([
                'refused_reason_id' => ['Selected refused reason is not active.'],
            ]);
        }

        if ($refusedReason->status !== $status) {
            throw ValidationException::withMessages([
                'refused_reason_id' => ['Selected refused reason does not belong to this status.'],
            ]);
        }

        return $refusedReason;
    }

    private function resolveLatestStatusNote(?string $freeTextReason, ?RefusedReason $refusedReason): ?string
    {
        if ($refusedReason instanceof RefusedReason) {
            return $refusedReason->reason;
        }

        $reason = trim((string) ($freeTextReason ?? ''));

        return $reason === '' ? null : $reason;
    }

    private function applyRefusedReasonPolicies(array $payload, Order $order, RefusedReason $refusedReason): array
    {
        if ((bool) $refusedReason->is_clear) {
            return [
                ...$payload,
                'total_amount' => 0,
                'shipping_fee' => 0,
                'commission_amount' => 0,
                'company_amount' => 0,
                'cod_amount' => 0,
                'latest_status_note' => $refusedReason->reason,
            ];
        }

        return $payload;
    }

    private function authorizeEditableColumns(Request $request, array $columns): void
    {
        foreach ($columns as $column) {
            $permission = OrdersPermissionMap::EDIT_COLUMNS[$column] ?? null;

            if ($permission && ! $request->user()?->can($permission)) {
                abort(403, "Missing permission: {$permission}");
            }
        }
    }

    private function filterVisibleColumns(Request $request, Order $order): array
    {
        $payload = $order->toArray();
        $result = [];

        foreach (OrdersPermissionMap::VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $payload[$column];
            }
        }

        if (! array_key_exists('id', $result)) {
            $result['id'] = $order->id;
        }

        // Convenience state for orders table collection tabs.
        $result['collection_state'] = $this->resolveCollectionState($order);
        $result['can_collect'] = ! (bool) $order->is_shipper_collected;

        // Dynamically compute redundant flags from relationships
        $result['is_in_shipper_collection'] ??= $order->shipperCollections()->where('shipper_collections.status', '!=', 'CANCELLED')->exists();
        $result['is_in_client_settlement'] ??= $order->clientSettlements()->where('client_settlements.status', '!=', 'CANCELLED')->exists();
        $result['is_in_shipper_return'] ??= $order->shipperReturns()->where('shipper_returns.status', '!=', 'CANCELLED')->exists();
        $result['is_in_client_return'] ??= $order->clientReturns()->where('client_returns.status', '!=', 'CANCELLED')->exists();

        return $result;
    }

    private function applyOrderSearch(Builder|QueryBuilder $query, array $validated): void
    {
        $generalSearch = isset($validated['q']) ? trim((string) $validated['q']) : '';

        if ($generalSearch !== '') {
            $query->where(function (Builder $builder) use ($generalSearch): void {
                $anyLike = '%'.$generalSearch.'%';
                $builder->where('code', 'like', $anyLike)
                    ->orWhere('external_code', 'like', $anyLike)
                    ->orWhere('total_amount', 'like', $anyLike)
                    ->orWhere('shipping_fee', 'like', $anyLike)
                    ->orWhere('commission_amount', 'like', $anyLike)
                    ->orWhere('company_amount', 'like', $anyLike)
                    ->orWhere('cod_amount', 'like', $anyLike)
                    ->orWhere('phone', 'like', $anyLike)
                    ->orWhere('phone_2', 'like', $anyLike)
                    ->orWhere('receiver_name', 'like', $anyLike)
                    ->orWhere('address', 'like', $anyLike)
                    ->orWhere('order_note', 'like', $anyLike)
                    ->orWhere('latest_status_note', 'like', $anyLike)
                    ->orWhere('status', 'like', $anyLike)
                    ->orWhereHas('governorate', fn (Builder $q) => $q->where('name', 'like', $anyLike))
                    ->orWhereHas('city', fn (Builder $q) => $q->where('name', 'like', $anyLike))
                    ->orWhereHas('shipper', fn (Builder $q) => $q->where('name', 'like', $anyLike))
                    ->orWhereHas('client', fn (Builder $q) => $q->where('name', 'like', $anyLike));

                $date = $this->parseFlexibleSearchDate($generalSearch);
                if ($date !== null) {
                    $builder->orWhereDate('created_at', $date)
                        ->orWhereDate('registered_at', $date)
                        ->orWhereDate('captain_date', $date)
                        ->orWhereDate('shipper_date', $date)
                        ->orWhereDate('shipper_collected_at', $date)
                        ->orWhereDate('client_settled_at', $date);
                }
            });
        }

        $columnSearch = is_array($validated['search'] ?? null) ? $validated['search'] : [];

        $collectionState = $validated['collection_state'] ?? $columnSearch['collection_state'] ?? null;
        if ($collectionState !== null && $collectionState !== '') {
            match ($collectionState) {
                'not_collected' => $query
                    ->whereDoesntHave('shipperCollections', function ($q) {
                        $q->where('status', '!=', 'CANCELLED');
                    })
                    ->where('is_shipper_collected', false),
                'ready_to_collect' => $query
                    ->whereHas('shipperCollections', function ($q) {
                        $q->where('status', '!=', 'CANCELLED');
                    })
                    ->where('is_shipper_collected', false),
                'collected' => $query->where('is_shipper_collected', true),
                default => null,
            };
            unset($columnSearch['collection_state']);
        }

        $statuses = [];
        $statusSources = [
            $validated['statuses'] ?? null,
            $validated['status'] ?? null,
            $columnSearch['statuses'] ?? null,
            $columnSearch['status'] ?? null,
        ];

        foreach ($statusSources as $s) {
            if ($s === null || $s === '') {
                continue;
            }
            if (is_string($s) && str_contains($s, ',')) {
                $s = explode(',', $s);
            }
            if (is_array($s)) {
                $statuses = array_merge($statuses, array_filter($s));
            } else {
                $statuses[] = $s;
            }
        }

        if (! empty($statuses)) {
            $query->whereIn('status', array_unique($statuses));
            unset($columnSearch['status'], $columnSearch['statuses']);
        }

        // 2. Approval Statuses
        $approvalStatuses = [];
        $approvalSources = [
            $validated['approval_statuses'] ?? null,
            $validated['approval_status'] ?? null,
            $columnSearch['approval_statuses'] ?? null,
            $columnSearch['approval_status'] ?? null,
        ];

        foreach ($approvalSources as $s) {
            if ($s === null || $s === '') {
                continue;
            }
            if (is_string($s) && str_contains($s, ',')) {
                $s = explode(',', $s);
            }
            if (is_array($s)) {
                $approvalStatuses = array_merge($approvalStatuses, array_filter($s));
            } else {
                $approvalStatuses[] = $s;
            }
        }

        if (! empty($approvalStatuses)) {
            $query->whereIn('approval_status', array_unique($approvalStatuses));
            unset($columnSearch['approval_status'], $columnSearch['approval_statuses']);
        }

        foreach (['shipper_return_pending', 'client_return_pending'] as $pendingFilter) {
            foreach ([$validated, $columnSearch] as $source) {
                if (! array_key_exists($pendingFilter, $source)) {
                    continue;
                }

                $value = $source[$pendingFilter];
                if ($value === null || $value === '') {
                    continue;
                }

                $isTrue = ($value === 'true' || $value === true || $value === '1' || $value === 1);

                if ($isTrue) {
                    if ($pendingFilter === 'shipper_return_pending') {
                        $query->eligibleForShipperReturn();
                    } else {
                        $query->eligibleForClientReturn();
                    }
                }

                unset($columnSearch[$pendingFilter]);
                break;
            }
        }

        $directFilters = [
            'code', 'external_code', 'receiver_name', 'phone', 'phone_2',
            'address', 'governorate_id',
            'city_id', 'shipper_user_id', 'client_user_id', 'allow_open',
            'has_return', 'is_shipper_collected',
            'is_client_settled',
            'is_shipper_returned',
            'is_client_returned',
            'order_note', 'latest_status_note', 'shipper_date',
            'total_amount', 'shipping_fee', 'commission_amount', 'company_amount', 'cod_amount',
        ];

        $appliedDirectFilters = [];

        foreach ($directFilters as $filter) {
            foreach ([$validated, $columnSearch] as $source) {
                if (! array_key_exists($filter, $source)) {
                    continue;
                }

                $value = $source[$filter];
                if ($value === null || $value === '') {
                    continue;
                }

                if (isset($appliedDirectFilters[$filter])) {
                    continue;
                }

                $this->applyDirectOrderFilter($query, $filter, $value);
                $appliedDirectFilters[$filter] = true;
            }
        }

        // 4. Handle removed flags via relationships
        $dynamicRelationMap = [
            'is_in_shipper_collection' => ['relation' => 'shipperCollections', 'table' => 'shipper_collections'],
            'is_in_client_settlement' => ['relation' => 'clientSettlements', 'table' => 'client_settlements'],
            'is_in_shipper_return' => ['relation' => 'shipperReturns', 'table' => 'shipper_returns'],
            'is_in_client_return' => ['relation' => 'clientReturns', 'table' => 'client_returns'],
        ];

        foreach ($dynamicRelationMap as $filter => $config) {
            foreach ([$validated, $columnSearch] as $source) {
                if (array_key_exists($filter, $source)) {
                    $value = $source[$filter];
                    if ($value === null || $value === '') {
                        continue;
                    }

                    $isTrue = ($value === 'true' || $value === true || $value === '1' || $value === 1);
                    $relation = $config['relation'];
                    $table = $config['table'];

                    if ($isTrue) {
                        $query->whereHas($relation, fn ($q) => $q->where("{$table}.status", '!=', 'CANCELLED'));
                    } else {
                        $query->whereDoesntHave($relation, fn ($q) => $q->where("{$table}.status", '!=', 'CANCELLED'));
                    }
                }
            }
        }
    }

    private function applyDirectOrderFilter(Builder $query, string $filter, mixed $value): void
    {
        if ($value === 'true' || $value === true || $value === '1' || $value === 1) {
            $value = 1;
        } elseif ($value === 'false' || $value === false || $value === '0' || $value === 0) {
            $value = 0;
        }

        if (is_string($value) && str_contains($value, ',')) {
            $value = explode(',', $value);
        }

        if ($filter === 'shipper_user_id' && is_string($value) && ! is_numeric($value)) {
            $name = trim($value);

            $query->whereHas('shipper', function (Builder $q) use ($name): void {
                $q->where('name', 'like', '%'.$name.'%');
            });

            return;
        }
        if ($filter === 'client_user_id' && is_string($value) && ! is_numeric($value)) {
            $name = trim($value);

            $query->whereHas('client', function (Builder $q) use ($name): void {
                $q->where('name', 'like', '%'.$name.'%');
            });

            return;
        }

        if ($filter === 'code' && is_string($value)) {
            $like = '%'.$value.'%';
            $query->where(function (Builder $q) use ($like): void {
                $q->where('code', 'like', $like)
                    ->orWhere('external_code', 'like', $like);
            });

            return;
        }

        if ($filter === 'receiver_name' && is_string($value)) {
            $like = '%'.$value.'%';
            $query->where(function (Builder $q) use ($like): void {
                $q->where('receiver_name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('phone_2', 'like', $like);
            });

            return;
        }

        if ($filter === 'shipper_date' && is_string($value)) {
            $date = $this->parseFlexibleSearchDate($value);

            if ($date !== null) {
                $query->whereDate('shipper_date', $date);
            } else {
                $query->where('shipper_date', 'like', '%'.$value.'%');
            }

            return;
        }

        $likeFilters = [
            'external_code', 'phone', 'phone_2', 'address',
            'order_note', 'latest_status_note',
            'total_amount', 'shipping_fee', 'commission_amount', 'company_amount', 'cod_amount',
        ];

        if (in_array($filter, $likeFilters, true) && is_string($value)) {
            $query->where($filter, 'like', '%'.$value.'%');

            return;
        }

        if (is_array($value)) {
            $query->whereIn($filter, $value);

            return;
        }

        $query->where($filter, $value);
    }

    private function parseFlexibleSearchDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $normalized = str_replace(['\\', '.', ' '], ['/', '/', ''], $value);
        $formats = [
            'Y-m-d',
            'Y/n/j',
            'Y/m/d',
            'd-m-Y',
            'j-n-Y',
            'd/m/Y',
            'j/n/Y',
            'd-m-y',
            'j-n-y',
            'd/m/y',
            'j/n/y',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $normalized);
                if ($date !== false && $date->format($format) === $normalized) {
                    return $date->toDateString();
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function resolveCollectionState(Order $order): string
    {
        if ((bool) $order->is_shipper_collected) {
            return 'collected';
        }

        if ($order->shipperCollections()->where('shipper_collections.status', '!=', 'CANCELLED')->exists()) {
            return 'ready_to_collect';
        }

        return 'not_collected';
    }

    private function calculateDisplayedTotals(array $rows): array
    {
        $totals = array_fill_keys(self::SUMMABLE_COLUMNS, 0.0);

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            foreach (self::SUMMABLE_COLUMNS as $column) {
                if (! array_key_exists($column, $row) || $row[$column] === null || $row[$column] === '') {
                    continue;
                }

                $totals[$column] += (float) $row[$column];
            }
        }

        return array_map(
            static fn (float $value): float => round($value, 2),
            $totals
        );
    }

    private function shouldRecalculateFinancials(array $data): bool
    {
        foreach (['total_amount', 'governorate_id', 'shipper_user_id', 'shipping_fee', 'commission_amount', 'company_amount', 'cod_amount'] as $field) {
            if (array_key_exists($field, $data)) {
                return true;
            }
        }

        return false;
    }

    private function applyAutomaticFinancials(array $data, ?Order $order = null): array
    {
        $clientUserId = $order?->client_user_id;
        $governorateId = $order?->governorate_id;
        $shipperUserId = $order?->shipper_user_id;
        $totalAmount = $order?->total_amount;

        if (array_key_exists('client_user_id', $data)) {
            $clientUserId = $data['client_user_id'];
        }

        if (array_key_exists('governorate_id', $data)) {
            $governorateId = $data['governorate_id'];
        }

        if (array_key_exists('shipper_user_id', $data)) {
            $shipperUserId = $data['shipper_user_id'];
        }

        if (array_key_exists('total_amount', $data)) {
            $totalAmount = $data['total_amount'];
        }

        if ($clientUserId === null || $governorateId === null || $totalAmount === null) {
            throw ValidationException::withMessages([
                'total_amount' => ['Unable to calculate order financials without client_user_id, governorate_id, and total_amount.'],
            ]);
        }

        $shouldResolveShippingFee = $order === null
            || array_key_exists('client_user_id', $data)
            || array_key_exists('governorate_id', $data);

        $shouldResolveCommissionAmount = $order === null
            || array_key_exists('shipper_user_id', $data);

        if (array_key_exists('shipping_fee', $data)) {
            $shippingFee = $data['shipping_fee'];
        } elseif ($shouldResolveShippingFee) {
            $shippingFee = $this->resolveShippingFee((int) $clientUserId, (int) $governorateId);
        } else {
            $shippingFee = $order?->shipping_fee;
        }

        if (array_key_exists('commission_amount', $data)) {
            $commissionAmount = $data['commission_amount'];
        } elseif ($shouldResolveCommissionAmount) {
            $commissionAmount = $this->resolveCommissionAmount($shipperUserId ? (int) $shipperUserId : null);
        } else {
            $commissionAmount = $order?->commission_amount;
        }

        // Fallback: If no plan assigned to client, use the representative's commission as the shipping fee
        if ($shippingFee === null) {
            if ($shipperUserId !== null) {
                $shippingFee = $commissionAmount;
            } else {
                throw ValidationException::withMessages([
                    'client_user_id' => ['Selected client has no plan assigned, and no shipper to fallback to for shipping fee calculation.'],
                ]);
            }
        }

        $total = round((float) $totalAmount, 2);

        $data['total_amount'] = $total;
        $data['shipping_fee'] = round((float) $shippingFee, 2);
        $data['commission_amount'] = round((float) $commissionAmount, 2);
        $formulaService = app(FinancialFormulaService::class);
        $variables = [
            'total_amount' => $total,
            'shipping_fee' => $data['shipping_fee'],
            'commission_amount' => $data['commission_amount'],
            'company_amount' => $data['company_amount'] ?? 0,
            'cod_amount' => $data['cod_amount'] ?? 0,
        ];

        $data['company_amount'] = $formulaService->calculate('formula_company_amount', $variables);
        $variables['company_amount'] = $data['company_amount'];
        $data['cod_amount'] = $formulaService->calculate('formula_cod_amount', $variables);

        return $data;
    }

    private function resolveShippingFee(int $clientUserId, int $governorateId): ?float
    {
        $planId = Client::query()
            ->where('user_id', $clientUserId)
            ->value('plan_id');

        if (! $planId) {
            return null;
        }

        $shippingFee = PlanPrice::query()
            ->where('plan_id', $planId)
            ->where('governorate_id', $governorateId)
            ->value('price');

        if ($shippingFee === null) {
            throw ValidationException::withMessages([
                'governorate_id' => ['No shipping fee found for the selected client plan and governorate.'],
            ]);
        }

        return round((float) $shippingFee, 2);
    }

    private function resolveCommissionAmount(?int $shipperUserId): float
    {
        if ($shipperUserId === null) {
            return 0.0;
        }

        $commission = Shipper::query()
            ->where('user_id', $shipperUserId)
            ->value('commission_rate');

        return round((float) ($commission ?? 0), 2);
    }

    private function resolveDefaultShipper(array $data, ?Order $order = null): array
    {
        $governorateId = $data['governorate_id'] ?? $order?->governorate_id;
        $shipperProvided = array_key_exists('shipper_user_id', $data);

        if (! $shipperProvided) {
            if ($order === null || ! array_key_exists('governorate_id', $data) || $order->shipper_user_id !== null) {
                return $data;
            }

            $data['shipper_user_id'] = null;
        }

        if ($data['shipper_user_id'] !== null && $data['shipper_user_id'] !== '') {
            return $data;
        }

        if ($governorateId === null) {
            throw ValidationException::withMessages([
                'shipper_user_id' => ['Please assign a shipper or select a governorate with a default shipper.'],
            ]);
        }

        $defaultShipperUserId = Governorate::query()
            ->whereKey($governorateId)
            ->value('default_shipper_user_id');

        if ($defaultShipperUserId === null) {
            throw ValidationException::withMessages([
                'shipper_user_id' => ['No default shipper found for the selected governorate. Please assign a shipper.'],
            ]);
        }

        $data['shipper_user_id'] = (int) $defaultShipperUserId;

        return $data;
    }

    // private function authorizeClientShipperMatchesGovernorate(Request $request, array $data, ?Order $order = null): void
    // {
    //     $shipperUserId = $data['shipper_user_id'] ?? $order?->shipper_user_id;
    //     if ($shipperUserId === null || $shipperUserId === '') {
    //         return;
    //     }

    //     $governorateId = $data['governorate_id'] ?? $order?->governorate_id;
    //     if ($governorateId === null) {
    //         return;
    //     }

    //     $defaultShipperUserId = Governorate::query()
    //         ->whereKey($governorateId)
    //         ->value('default_shipper_user_id');

    //     $isAssignedShipper = Governorate::query()
    //         ->whereKey($governorateId)
    //         ->whereHas('shippers', fn (Builder $query) => $query->where('users.id', $shipperUserId))
    //         ->exists();

    //     if ((int) $defaultShipperUserId !== (int) $shipperUserId && ! $isAssignedShipper) {
    //         throw ValidationException::withMessages([
    //             'shipper_user_id' => ['Selected shipper is not assigned to the selected governorate.'],
    //         ]);
    //     }
    // }

    private function buildClientTimelineEntry($log): array
    {
        $newValues = is_array($log->new_values) ? $log->new_values : [];
        $oldValues = is_array($log->old_values) ? $log->old_values : [];

        $status = $newValues['status'] ?? $oldValues['status'] ?? null;
        $statusLabel = $status && array_key_exists($status, self::STATUS_LABELS)
            ? self::STATUS_LABELS[$status]
            : (string) ($status ?? 'Unknown');

        return [
            'id' => $log->id,
            'status' => $status,
            'message' => "Shipment status is {$statusLabel}",
            'created_at' => $log->created_at,
        ];
    }

    private function formatMyOrderRow(Order $order): array
    {
        $phones = array_values(array_filter([
            $order->phone,
            $order->phone_2,
        ], static fn ($phone): bool => $phone !== null && $phone !== ''));

        $address = implode(' \\ ', array_values(array_filter([
            $order->governorate?->name,
            $order->city?->name,
            $order->address,
        ], static fn ($part): bool => $part !== null && $part !== '')));

        return [
            'id' => $order->id,
            'receiver_name' => $order->receiver_name,
            'phones' => $phones,
            'phones_text' => implode(' - ', $phones),
            'address' => $address,
            'total_amount' => $order->total_amount,
            'cod' => $order->cod_amount,
        ];
    }

    /**
     * Recalculate pivot records and parent totals for any active financial documents
     * (shipper collections and client settlements) containing this order.
     */
    private function recalculateRelatedFinancialDocuments(Order $order): void
    {
        $formulaService = app(FinancialFormulaService::class);

        // --- Shipper Collections ---
        $collectionPivots = ShipperCollectionOrder::where('order_id', $order->id)->get();
        foreach ($collectionPivots as $pivot) {
            $collection = ShipperCollection::find($pivot->shipper_collection_id);
            if (! $collection || $collection->status === 'CANCELLED') {
                continue;
            }

            // Update pivot record with fresh order values
            $newNetAmount = $formulaService->calculate('formula_shipper_collection_net_amount', [
                'total_amount' => (float) $order->total_amount,
                'shipping_fee' => (float) $order->shipping_fee,
                'commission_amount' => (float) $order->commission_amount,
                'company_amount' => (float) $order->company_amount,
                'cod_amount' => (float) $order->cod_amount,
                'settlement_fees' => 0,
            ]);

            $pivot->update([
                'order_amount' => $order->total_amount,
                'shipper_fee' => $order->commission_amount,
                'net_amount' => round($newNetAmount, 2),
            ]);

            // Recalculate collection totals from all its pivot records.
            $allPivots = ShipperCollectionOrder::where('shipper_collection_id', $collection->id)->get();
            $collectionTotalAmount = round($allPivots->sum('order_amount'), 2);
            $collectionShipperFees = round($allPivots->sum('shipper_fee'), 2);
            $collection->update([
                'total_amount' => $collectionTotalAmount,
                'shipper_fees' => $collectionShipperFees,
                'net_amount' => round($collectionTotalAmount - $collectionShipperFees, 2),
                'number_of_orders' => $allPivots->count(),
            ]);
        }

        // --- Client Settlements ---
        $settlementPivots = ClientSettlementOrder::where('order_id', $order->id)->get();
        foreach ($settlementPivots as $pivot) {
            $settlement = ClientSettlement::find($pivot->client_settlement_id);
            if (! $settlement || $settlement->status === 'CANCELLED') {
                continue;
            }

            // Update pivot record with fresh order values
            $newNetAmount = $formulaService->calculate('formula_client_settlement_net_amount', [
                'total_amount' => (float) $order->total_amount,
                'shipping_fee' => (float) $order->shipping_fee,
                'commission_amount' => (float) $order->commission_amount,
                'company_amount' => (float) $order->company_amount,
                'cod_amount' => (float) $order->cod_amount,
                'settlement_fees' => 0,
            ]);

            $pivot->update([
                'order_amount' => $order->total_amount,
                'fee' => $order->shipping_fee,
                'net_amount' => round($newNetAmount, 2),
            ]);

            // Recalculate settlement totals from all its pivot records
            $allPivots = ClientSettlementOrder::where('client_settlement_id', $settlement->id)->get();
            $settlement->update([
                'total_amount' => round($allPivots->sum('order_amount'), 2),
                'fees' => round($allPivots->sum('fee'), 2),
                'net_amount' => round($allPivots->sum('net_amount'), 2),
                'number_of_orders' => $allPivots->count(),
            ]);
        }
    }
}
