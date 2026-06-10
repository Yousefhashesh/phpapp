<?php

namespace App\Support\Permissions;

class OperationsPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'material.page', 'group' => 'materials', 'label' => 'دخول صفحة الخامات', 'type' => 'page'],
        ['name' => 'material-request.page', 'group' => 'material-requests', 'label' => 'دخول صفحة طلبات الخامات', 'type' => 'page'],
        ['name' => 'material-request-item.page', 'group' => 'material-request-items', 'label' => 'دخول صفحة بنود طلبات الخامات', 'type' => 'page'],
        ['name' => 'pickup-request.page', 'group' => 'pickup-requests', 'label' => 'دخول صفحة طلبات البيك أب', 'type' => 'page'],
        ['name' => 'visit.page', 'group' => 'visits', 'label' => 'دخول صفحة الزيارات', 'type' => 'page'],
    ];

    public const ACTION_PERMISSIONS = [
        ['name' => 'material.view', 'group' => 'materials', 'label' => 'عرض الخامات', 'type' => 'action'],
        ['name' => 'material.create', 'group' => 'materials', 'label' => 'إضافة خامة جديدة', 'type' => 'button'],
        ['name' => 'material.update', 'group' => 'materials', 'label' => 'تعديل خامة', 'type' => 'button'],
        ['name' => 'material.delete', 'group' => 'materials', 'label' => 'حذف خامة', 'type' => 'button'],

        ['name' => 'material-request.view', 'group' => 'material-requests', 'label' => 'عرض طلبات الخامات', 'type' => 'action'],
        ['name' => 'material-request.create', 'group' => 'material-requests', 'label' => 'إنشاء طلب خامات', 'type' => 'button'],
        ['name' => 'material-request.update', 'group' => 'material-requests', 'label' => 'تعديل طلب خامات', 'type' => 'button'],
        ['name' => 'material-request.delete', 'group' => 'material-requests', 'label' => 'حذف طلب خامات', 'type' => 'button'],

        ['name' => 'material-request-item.view', 'group' => 'material-request-items', 'label' => 'عرض بنود طلبات الخامات', 'type' => 'action'],
        ['name' => 'material-request-item.create', 'group' => 'material-request-items', 'label' => 'إضافة بند لطلب خامات', 'type' => 'button'],
        ['name' => 'material-request-item.update', 'group' => 'material-request-items', 'label' => 'تعديل بند طلب خامات', 'type' => 'button'],
        ['name' => 'material-request-item.delete', 'group' => 'material-request-items', 'label' => 'حذف بند طلب خامات', 'type' => 'button'],

        ['name' => 'pickup-request.view', 'group' => 'pickup-requests', 'label' => 'عرض طلبات البيك أب', 'type' => 'action'],
        ['name' => 'pickup-request.create', 'group' => 'pickup-requests', 'label' => 'إنشاء طلب بيك أب', 'type' => 'button'],
        ['name' => 'pickup-request.update', 'group' => 'pickup-requests', 'label' => 'تعديل طلب بيك أب', 'type' => 'button'],
        ['name' => 'pickup-request.delete', 'group' => 'pickup-requests', 'label' => 'حذف طلب بيك أب', 'type' => 'button'],

        ['name' => 'visit.view', 'group' => 'visits', 'label' => 'عرض الزيارات', 'type' => 'action'],
        ['name' => 'visit.create', 'group' => 'visits', 'label' => 'إضافة زيارة جديدة', 'type' => 'button'],
        ['name' => 'visit.update', 'group' => 'visits', 'label' => 'تعديل زيارة', 'type' => 'button'],
        ['name' => 'visit.delete', 'group' => 'visits', 'label' => 'حذف زيارة', 'type' => 'button'],
    ];

    private const ENTITY_NAMES_AR = [
        'material' => 'الخامات',
        'material request' => 'طلب الخامات',
        'material request item' => 'بند طلب الخامات',
        'pickup request' => 'طلب بيك أب',
        'visit' => 'الزيارة',
    ];

    private const COLUMN_NAMES_AR = [
        'id' => 'المعرف',
        'name' => 'الاسم',
        'code' => 'الكود',
        'cost_price' => 'سعر التكلفة',
        'sale_price' => 'سعر البيع',
        'stock' => 'المخزون',
        'is_active' => 'نشط',
        'notes' => 'ملاحظات',
        'client_id' => 'العميل',
        'shipper_id' => 'المندوب',
        'delivery_type' => 'نوع التوصيل',
        'combined_visit' => 'زيارة مدمجة',
        'materials_total' => 'إجمالي الخامات',
        'shipping_cost' => 'تكلفة الشحن',
        'status' => 'الحالة',
        'approval_status' => 'حالة الموافقة',
        'created_by' => 'أنشئ بواسطة',
        'approved_by' => 'وافق عليه',
        'approved_at' => 'تاريخ الموافقة',
        'rejected_by' => 'رفضه',
        'rejected_at' => 'تاريخ الرفض',
        'approval_note' => 'ملاحظة الموافقة',
        'items' => 'البنود',
        'material_request_id' => 'رقم طلب الخامات',
        'material_id' => 'الخامة',
        'quantity' => 'الكمية',
        'price' => 'السعر',
        'total' => 'الإجمالي',
        'pickup_date' => 'تاريخ البيك أب',
        'combined_with_material' => 'مدمج مع خامات',
        'pickup_cost' => 'تكلفة البيك أب',
        'pickup_request_id' => 'رقم طلب البيك أب',
        'visit_cost' => 'تكلفة الزيارة',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
    ];

    public const MATERIAL_VIEW_COLUMNS = [
        'id' => null,
        'name' => 'material.column.name.view',
        'code' => 'material.column.code.view',
        'cost_price' => 'material.column.cost_price.view',
        'sale_price' => 'material.column.sale_price.view',
        'stock' => 'material.column.stock.view',
        'is_active' => 'material.column.is_active.view',
        'notes' => 'material.column.notes.view',
        'created_at' => 'material.column.created_at.view',
        'updated_at' => 'material.column.updated_at.view',
    ];

    public const MATERIAL_EDIT_COLUMNS = [
        'name' => 'material.column.name.edit',
        'code' => 'material.column.code.edit',
        'cost_price' => 'material.column.cost_price.edit',
        'sale_price' => 'material.column.sale_price.edit',
        'stock' => 'material.column.stock.edit',
        'is_active' => 'material.column.is_active.edit',
        'notes' => 'material.column.notes.edit',
    ];

    public const MATERIAL_REQUEST_VIEW_COLUMNS = [
        'id' => null,
        'client_id' => 'material-request.column.client_id.view',
        'shipper_id' => 'material-request.column.shipper_id.view',
        'delivery_type' => 'material-request.column.delivery_type.view',
        'combined_visit' => 'material-request.column.combined_visit.view',
        'materials_total' => 'material-request.column.materials_total.view',
        'shipping_cost' => 'material-request.column.shipping_cost.view',
        'status' => 'material-request.column.status.view',
        'approval_status' => 'material-request.column.approval_status.view',
        'created_by' => 'material-request.column.created_by.view',
        'approved_by' => 'material-request.column.approved_by.view',
        'approved_at' => 'material-request.column.approved_at.view',
        'rejected_by' => 'material-request.column.rejected_by.view',
        'rejected_at' => 'material-request.column.rejected_at.view',
        'approval_note' => 'material-request.column.approval_note.view',
        'items' => 'material-request.column.items.view',
        'client' => 'material-request.column.client_id.view',
        'shipper' => 'material-request.column.shipper_id.view',
        'created_at' => 'material-request.column.created_at.view',
        'updated_at' => 'material-request.column.updated_at.view',
    ];

    public const MATERIAL_REQUEST_EDIT_COLUMNS = [
        'client_id' => 'material-request.column.client_id.edit',
        'shipper_id' => 'material-request.column.shipper_id.edit',
        'delivery_type' => 'material-request.column.delivery_type.edit',
        'combined_visit' => 'material-request.column.combined_visit.edit',
        'materials_total' => 'material-request.column.materials_total.edit',
        'shipping_cost' => 'material-request.column.shipping_cost.edit',
        'status' => 'material-request.column.status.edit',
        'approval_status' => 'material-request.column.approval_status.edit',
        'created_by' => 'material-request.column.created_by.edit',
        'approved_by' => 'material-request.column.approved_by.edit',
        'approved_at' => 'material-request.column.approved_at.edit',
        'rejected_by' => 'material-request.column.rejected_by.edit',
        'rejected_at' => 'material-request.column.rejected_at.edit',
        'approval_note' => 'material-request.column.approval_note.edit',
    ];

    public const MATERIAL_REQUEST_ITEM_VIEW_COLUMNS = [
        'id' => null,
        'material_request_id' => 'material-request-item.column.material_request_id.view',
        'material_id' => 'material-request-item.column.material_id.view',
        'quantity' => 'material-request-item.column.quantity.view',
        'price' => 'material-request-item.column.price.view',
        'total' => 'material-request-item.column.total.view',
        'material' => 'material-request-item.column.material.view',
        'created_at' => 'material-request-item.column.created_at.view',
        'updated_at' => 'material-request-item.column.updated_at.view',
    ];

    public const MATERIAL_REQUEST_ITEM_EDIT_COLUMNS = [
        'material_request_id' => 'material-request-item.column.material_request_id.edit',
        'material_id' => 'material-request-item.column.material_id.edit',
        'quantity' => 'material-request-item.column.quantity.edit',
        'price' => 'material-request-item.column.price.edit',
        'total' => 'material-request-item.column.total.edit',
    ];

    public const PICKUP_REQUEST_VIEW_COLUMNS = [
        'id' => null,
        'client_id' => 'pickup-request.column.client_id.view',
        'shipper_id' => 'pickup-request.column.shipper_id.view',
        'pickup_date' => 'pickup-request.column.pickup_date.view',
        'combined_with_material' => 'pickup-request.column.combined_with_material.view',
        'pickup_cost' => 'pickup-request.column.pickup_cost.view',
        'status' => 'pickup-request.column.status.view',
        'approval_status' => 'pickup-request.column.approval_status.view',
        'created_by' => 'pickup-request.column.created_by.view',
        'approved_by' => 'pickup-request.column.approved_by.view',
        'approved_at' => 'pickup-request.column.approved_at.view',
        'rejected_by' => 'pickup-request.column.rejected_by.view',
        'rejected_at' => 'pickup-request.column.rejected_at.view',
        'approval_note' => 'pickup-request.column.approval_note.view',
        'notes' => 'pickup-request.column.notes.view',
        'client' => 'pickup-request.column.client_id.view',
        'shipper' => 'pickup-request.column.shipper_id.view',
        'created_at' => 'pickup-request.column.created_at.view',
        'updated_at' => 'pickup-request.column.updated_at.view',
    ];

    public const PICKUP_REQUEST_EDIT_COLUMNS = [
        'client_id' => 'pickup-request.column.client_id.edit',
        'shipper_id' => 'pickup-request.column.shipper_id.edit',
        'pickup_date' => 'pickup-request.column.pickup_date.edit',
        'combined_with_material' => 'pickup-request.column.combined_with_material.edit',
        'pickup_cost' => 'pickup-request.column.pickup_cost.edit',
        'status' => 'pickup-request.column.status.edit',
        'approval_status' => 'pickup-request.column.approval_status.edit',
        'created_by' => 'pickup-request.column.created_by.edit',
        'approved_by' => 'pickup-request.column.approved_by.edit',
        'approved_at' => 'pickup-request.column.approved_at.edit',
        'rejected_by' => 'pickup-request.column.rejected_by.edit',
        'rejected_at' => 'pickup-request.column.rejected_at.edit',
        'approval_note' => 'pickup-request.column.approval_note.edit',
        'notes' => 'pickup-request.column.notes.edit',
    ];

    public const VISIT_VIEW_COLUMNS = [
        'id' => null,
        'shipper_id' => 'visit.column.shipper_id.view',
        'client_id' => 'visit.column.client_id.view',
        'pickup_request_id' => 'visit.column.pickup_request_id.view',
        'material_request_id' => 'visit.column.material_request_id.view',
        'visit_cost' => 'visit.column.visit_cost.view',
        'client' => 'visit.column.client_id.view',
        'shipper' => 'visit.column.shipper_id.view',
        'created_at' => 'visit.column.created_at.view',
        'updated_at' => 'visit.column.updated_at.view',
    ];

    public const VISIT_EDIT_COLUMNS = [
        'shipper_id' => 'visit.column.shipper_id.edit',
        'client_id' => 'visit.column.client_id.edit',
        'pickup_request_id' => 'visit.column.pickup_request_id.edit',
        'material_request_id' => 'visit.column.material_request_id.edit',
        'visit_cost' => 'visit.column.visit_cost.edit',
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

        $columnSets = [
            ['group' => 'materials', 'view' => self::MATERIAL_VIEW_COLUMNS, 'edit' => self::MATERIAL_EDIT_COLUMNS, 'entity' => 'material'],
            ['group' => 'material-requests', 'view' => self::MATERIAL_REQUEST_VIEW_COLUMNS, 'edit' => self::MATERIAL_REQUEST_EDIT_COLUMNS, 'entity' => 'material request'],
            ['group' => 'material-request-items', 'view' => self::MATERIAL_REQUEST_ITEM_VIEW_COLUMNS, 'edit' => self::MATERIAL_REQUEST_ITEM_EDIT_COLUMNS, 'entity' => 'material request item'],
            ['group' => 'pickup-requests', 'view' => self::PICKUP_REQUEST_VIEW_COLUMNS, 'edit' => self::PICKUP_REQUEST_EDIT_COLUMNS, 'entity' => 'pickup request'],
            ['group' => 'visits', 'view' => self::VISIT_VIEW_COLUMNS, 'edit' => self::VISIT_EDIT_COLUMNS, 'entity' => 'visit'],
        ];

        foreach ($columnSets as $set) {
            foreach ($set['view'] as $column => $permission) {
                if ($permission === null) {
                    continue;
                }

                $permissions[] = [
                    'name' => $permission,
                    'group' => $set['group'],
                    'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في ' . (self::ENTITY_NAMES_AR[$set['entity']] ?? $set['entity']),
                    'type' => 'column',
                ];
            }

            foreach ($set['edit'] as $column => $permission) {
                $permissions[] = [
                    'name' => $permission,
                    'group' => $set['group'],
                    'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في ' . (self::ENTITY_NAMES_AR[$set['entity']] ?? $set['entity']),
                    'type' => 'column',
                ];
            }
        }

        return $permissions;
    }
}
