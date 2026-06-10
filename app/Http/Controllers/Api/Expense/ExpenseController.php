<?php

namespace App\Http\Controllers\Api\Expense;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Support\Permissions\ExpensePermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'expense.page');
        $this->authorizePermission($request, 'expense.view');

        $expenses = Expense::query()
            ->forUserRole()
            ->with(['category', 'createdBy:id,name', 'approvedBy:id,name'])
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $expenses->map(fn (Expense $expense): array => $this->filterVisibleColumns($request, $expense))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'expense.create');

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:255', 'unique:expenses,code'],
            'category_id' => ['nullable', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'exists:users,id'],
            'approved_by' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', Rule::in(['PENDING', 'APPROVED', 'REJECTED', 'PAID', 'CANCELLED'])],
            'approved_at' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data), ExpensePermissionMap::EXPENSE_EDIT_COLUMNS);
        $this->authorizeStatusButtonPermission($request, null, $data['status'] ?? null);

        if (! isset($data['created_by']) && $request->user()) {
            $data['created_by'] = $request->user()->id;
        }

        $expense = Expense::query()->create($data);
        $expense->load(['category', 'createdBy:id,name', 'approvedBy:id,name']);

        return response()->json([
            'message' => 'Expense created successfully.',
            'data' => $this->filterVisibleColumns($request, $expense),
        ], 201);
    }

    public function show(Request $request, Expense $expense): JsonResponse
    {
        $this->authorizePermission($request, 'expense.page');
        $this->authorizePermission($request, 'expense.view');

        $expense->load(['category', 'createdBy:id,name', 'approvedBy:id,name']);

        return response()->json($this->filterVisibleColumns($request, $expense));
    }

    public function update(Request $request, Expense $expense): JsonResponse
    {
        $this->authorizePermission($request, 'expense.update');

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:255', Rule::unique('expenses', 'code')->ignore($expense->id)],
            'category_id' => ['nullable', 'exists:expense_categories,id'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'expense_date' => ['sometimes', 'required', 'date'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'exists:users,id'],
            'approved_by' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', Rule::in(['PENDING', 'APPROVED', 'REJECTED', 'PAID', 'CANCELLED'])],
            'approved_at' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data), ExpensePermissionMap::EXPENSE_EDIT_COLUMNS);
        $this->authorizeStatusButtonPermission($request, $expense->status, $data['status'] ?? null);

        $expense->update($data);
        $expense->load(['category', 'createdBy:id,name', 'approvedBy:id,name']);

        return response()->json([
            'message' => 'Expense updated successfully.',
            'data' => $this->filterVisibleColumns($request, $expense),
        ]);
    }

    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        $this->authorizePermission($request, 'expense.delete');

        $expense->delete();

        return response()->json([
            'message' => 'Expense deleted successfully.',
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
                abort(403, "Missing permission for column [{$column}]: {$permission}");
            }
        }
    }

    private function authorizeStatusButtonPermission(Request $request, ?string $fromStatus, ?string $toStatus): void
    {
        if (! $toStatus || $toStatus === $fromStatus) {
            return;
        }

        $buttonPermission = ExpensePermissionMap::STATUS_BUTTON_PERMISSIONS[$toStatus] ?? null;

        if ($buttonPermission && ! $request->user()?->can($buttonPermission)) {
            abort(403, "Missing button permission for status transition: {$buttonPermission}");
        }
    }

    private function filterVisibleColumns(Request $request, Expense $expense): array
    {
        $raw = $expense->toArray();
        $result = [];

        foreach (ExpensePermissionMap::EXPENSE_VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $raw)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $raw[$column];
            }
        }

        if ($request->user()?->can('expense.column.category_id.view')) {
            $result['category'] = $expense->relationLoaded('category') ? $expense->category : null;
        }

        if ($request->user()?->can('expense.column.created_by.view')) {
            $result['created_by_user'] = $expense->relationLoaded('createdBy') ? $expense->createdBy : null;
        }

        if ($request->user()?->can('expense.column.approved_by.view')) {
            $result['approved_by_user'] = $expense->relationLoaded('approvedBy') ? $expense->approvedBy : null;
        }

        return $result;
    }
}
