<?php

namespace App\Support\Permissions;

class ExpensePermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'expense.page', 'group' => 'expenses', 'label' => 'دخول صفحة المصروفات', 'type' => 'page'],
        ['name' => 'expense-category.page', 'group' => 'expense-categories', 'label' => 'دخول صفحة تصنيفات المصروفات', 'type' => 'page'],
    ];

    public const ACTION_PERMISSIONS = [
        ['name' => 'expense.view', 'group' => 'expenses', 'label' => 'عرض المصروفات', 'type' => 'action'],
        ['name' => 'expense.create', 'group' => 'expenses', 'label' => 'إضافة مصروف جديد', 'type' => 'button'],
        ['name' => 'expense.update', 'group' => 'expenses', 'label' => 'تعديل مصروف', 'type' => 'button'],
        ['name' => 'expense.delete', 'group' => 'expenses', 'label' => 'حذف مصروف', 'type' => 'button'],

        ['name' => 'expense.button.approve', 'group' => 'expenses', 'label' => 'الموافقة على المصروف', 'type' => 'button'],
        ['name' => 'expense.button.reject', 'group' => 'expenses', 'label' => 'رفض المصروف', 'type' => 'button'],
        ['name' => 'expense.button.mark-paid', 'group' => 'expenses', 'label' => 'تحديد المصروف كمدفوع', 'type' => 'button'],
        ['name' => 'expense.button.cancel', 'group' => 'expenses', 'label' => 'إلغاء المصروف', 'type' => 'button'],

        ['name' => 'expense-category.view', 'group' => 'expense-categories', 'label' => 'عرض تصنيفات المصروفات', 'type' => 'action'],
        ['name' => 'expense-category.create', 'group' => 'expense-categories', 'label' => 'إضافة تصنيف مصروفات جديد', 'type' => 'button'],
        ['name' => 'expense-category.update', 'group' => 'expense-categories', 'label' => 'تعديل تصنيف مصروفات', 'type' => 'button'],
        ['name' => 'expense-category.delete', 'group' => 'expense-categories', 'label' => 'حذف تصنيف مصروفات', 'type' => 'button'],
    ];

    private const COLUMN_NAMES_AR = [
        'id' => 'المعرف',
        'code' => 'كود المصروف',
        'category_id' => 'التصنيف',
        'amount' => 'المبلغ',
        'expense_date' => 'تاريخ الصرف',
        'title' => 'العنوان/البيان',
        'notes' => 'الملاحظات',
        'created_by' => 'أنشئ بواسطة',
        'approved_by' => 'وافق عليه',
        'status' => 'الحالة',
        'approved_at' => 'تاريخ الموافقة',
        'paid_at' => 'تاريخ الدفع',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
        'deleted_at' => 'تاريخ الحذف',
        'name' => 'اسم التصنيف',
        'is_active' => 'نشط',
    ];

    public const EXPENSE_VIEW_COLUMNS = [
        'id' => null,
        'code' => 'expense.column.code.view',
        'category_id' => 'expense.column.category_id.view',
        'amount' => 'expense.column.amount.view',
        'expense_date' => 'expense.column.expense_date.view',
        'title' => 'expense.column.title.view',
        'notes' => 'expense.column.notes.view',
        'created_by' => 'expense.column.created_by.view',
        'approved_by' => 'expense.column.approved_by.view',
        'status' => 'expense.column.status.view',
        'approved_at' => 'expense.column.approved_at.view',
        'paid_at' => 'expense.column.paid_at.view',
        'created_at' => 'expense.column.created_at.view',
        'updated_at' => 'expense.column.updated_at.view',
        'deleted_at' => 'expense.column.deleted_at.view',
    ];

    public const EXPENSE_EDIT_COLUMNS = [
        'code' => 'expense.column.code.edit',
        'category_id' => 'expense.column.category_id.edit',
        'amount' => 'expense.column.amount.edit',
        'expense_date' => 'expense.column.expense_date.edit',
        'title' => 'expense.column.title.edit',
        'notes' => 'expense.column.notes.edit',
        'created_by' => 'expense.column.created_by.edit',
        'approved_by' => 'expense.column.approved_by.edit',
        'status' => 'expense.column.status.edit',
        'approved_at' => 'expense.column.approved_at.edit',
        'paid_at' => 'expense.column.paid_at.edit',
    ];

    public const EXPENSE_CATEGORY_VIEW_COLUMNS = [
        'id' => null,
        'name' => 'expense-category.column.name.view',
        'notes' => 'expense-category.column.notes.view',
        'is_active' => 'expense-category.column.is_active.view',
        'created_at' => 'expense-category.column.created_at.view',
        'updated_at' => 'expense-category.column.updated_at.view',
    ];

    public const EXPENSE_CATEGORY_EDIT_COLUMNS = [
        'name' => 'expense-category.column.name.edit',
        'notes' => 'expense-category.column.notes.edit',
        'is_active' => 'expense-category.column.is_active.edit',
    ];

    public const STATUS_BUTTON_PERMISSIONS = [
        'APPROVED' => 'expense.button.approve',
        'REJECTED' => 'expense.button.reject',
        'PAID' => 'expense.button.mark-paid',
        'CANCELLED' => 'expense.button.cancel',
    ];

    /**
     * @return array<int, array{name:string,group:string,label:string,type:string}>
     */
    public static function allPermissionDefinitions(): array
    {
        $permissions = [
            ...self::PAGE_PERMISSIONS,
            ...self::ACTION_PERMISSIONS,
        ];

        foreach (self::EXPENSE_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'expenses',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في المصروفات',
                'type' => 'column',
            ];
        }

        foreach (self::EXPENSE_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'expenses',
                'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في المصروفات',
                'type' => 'column',
            ];
        }

        foreach (self::EXPENSE_CATEGORY_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'expense-categories',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في تصنيف المصروفات',
                'type' => 'column',
            ];
        }

        foreach (self::EXPENSE_CATEGORY_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'expense-categories',
                'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في تصنيف المصروفات',
                'type' => 'column',
            ];
        }

        return $permissions;
    }
}
