<?php

namespace App\Http\Controllers\Api\Expense;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Support\Permissions\ExpensePermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'expense-category.page');
        $this->authorizePermission($request, 'expense-category.view');

        $categories = ExpenseCategory::query()
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $categories->map(fn (ExpenseCategory $category): array => $this->filterVisibleColumns($request, $category))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'expense-category.create');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:expense_categories,name'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data), ExpensePermissionMap::EXPENSE_CATEGORY_EDIT_COLUMNS);

        $category = ExpenseCategory::query()->create($data);

        return response()->json([
            'message' => 'Expense category created successfully.',
            'data' => $this->filterVisibleColumns($request, $category),
        ], 201);
    }

    public function show(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->authorizePermission($request, 'expense-category.page');
        $this->authorizePermission($request, 'expense-category.view');

        return response()->json($this->filterVisibleColumns($request, $expenseCategory));
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->authorizePermission($request, 'expense-category.update');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_categories', 'name')->ignore($expenseCategory->id),
            ],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data), ExpensePermissionMap::EXPENSE_CATEGORY_EDIT_COLUMNS);

        $expenseCategory->update($data);

        return response()->json([
            'message' => 'Expense category updated successfully.',
            'data' => $this->filterVisibleColumns($request, $expenseCategory),
        ]);
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->authorizePermission($request, 'expense-category.delete');

        $expenseCategory->delete();

        return response()->json([
            'message' => 'Expense category deleted successfully.',
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    /**
     * @param array<int, string> $columns
     * @param array<string, string> $columnPermissions
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

    private function filterVisibleColumns(Request $request, ExpenseCategory $expenseCategory): array
    {
        $result = [];
        $raw = $expenseCategory->toArray();

        foreach (ExpensePermissionMap::EXPENSE_CATEGORY_VIEW_COLUMNS as $column => $permission) {
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
