<?php

namespace App\Support\Permissions;

class OrdersPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'order.page', 'group' => 'orders', 'label' => 'دخول صفحة الأوردات', 'type' => 'page'],
        ['name' => 'order.scan.page', 'group' => 'orders', 'label' => 'دخول صفحة المسح (باركود)', 'type' => 'page'],
        ['name' => 'order.approval.page', 'group' => 'orders', 'label' => 'دخول صفحة طلبات الموافقة', 'type' => 'page'],
        ['name' => 'order.hold-outfordelivery.page', 'group' => 'orders', 'label' => 'دخول صفحة قيد التسليم والانتظار', 'type' => 'page'],
        ['name' => 'order.uncollectedclient.page', 'group' => 'orders', 'label' => 'دخول صفحة تحصيل العملاء غير المحصلة', 'type' => 'page'],
        ['name' => 'order.uncollectedshipper.page', 'group' => 'orders', 'label' => 'دخول صفحة تحصيل المناديب غير المحصلة', 'type' => 'page'],
        ['name' => 'order.unreturnshipper.page', 'group' => 'orders', 'label' => 'دخول صفحة مرتجع المناديب غير المرتجع', 'type' => 'page'],
        ['name' => 'order.unreturnclient.page', 'group' => 'orders', 'label' => 'دخول صفحة مرتجع العملاء غير المرتجع', 'type' => 'page'],
    ];

    public const ACTION_PERMISSIONS = [
        ['name' => 'order.view', 'group' => 'orders', 'label' => 'عرض الأوردات', 'type' => 'action'],
        ['name' => 'order.create', 'group' => 'orders', 'label' => 'إنشاء أوردر جديد', 'type' => 'button'],
        ['name' => 'order.update', 'group' => 'orders', 'label' => 'تحديث الأوردر', 'type' => 'button'],
        ['name' => 'order.update-after-final-status', 'group' => 'orders', 'label' => 'تحديث الأوردر بعد الحالة النهائية', 'type' => 'button'],
        ['name' => 'order.delete', 'group' => 'orders', 'label' => 'حذف الأوردر', 'type' => 'button'],
        ['name' => 'order.change-status', 'group' => 'orders', 'label' => 'تغيير حالة الأوردر', 'type' => 'button'],
        ['name' => 'order.change-shipper', 'group' => 'orders', 'label' => 'تغيير المندوب', 'type' => 'button'],
        ['name' => 'order.change-note', 'group' => 'orders', 'label' => 'تغيير ملاحظة الأوردر', 'type' => 'button'],
        ['name' => 'order.change-external-code', 'group' => 'orders', 'label' => 'تغيير الكود الخارجي', 'type' => 'button'],
        ['name' => 'order.my-orders', 'group' => 'orders', 'label' => 'عرض أورداتي', 'type' => 'button'],
        ['name' => 'order.approve', 'group' => 'orders', 'label' => 'الموافقة على الأوردر', 'type' => 'button'],
        ['name' => 'order.reject', 'group' => 'orders', 'label' => 'رفض الأوردر', 'type' => 'button'],
        ['name' => 'order.export', 'group' => 'orders', 'label' => 'تصدير الأوردات (إكسيل)', 'type' => 'button'],
        ['name' => 'order.import', 'group' => 'orders', 'label' => 'استيراد الأوردات (إكسيل)', 'type' => 'button'],
    ];

    private const COLUMN_NAMES_AR = [
        'id' => 'المعرف',
        'code' => 'كود الأوردر',
        'external_code' => 'الكود الخارجي',
        'receiver_name' => 'اسم المستلم',
        'phone' => 'الهاتف',
        'phone_2' => 'الهاتف 2',
        'address' => 'العنوان',
        'governorate_id' => 'المحافظة',
        'city_id' => 'المدينة',
        'total_amount' => 'إجمالي المبلغ',
        'shipping_fee' => 'مصاريف الشحن',
        'commission_amount' => 'العمولة',
        'company_amount' => 'مبلغ الشركة',
        'cod_amount' => 'مبلغ التحصيل',
        'status' => 'الحالة',
        'latest_status_note' => 'ملاحظة الحالة',
        'shipper_date' => 'تاريخ المندوب',
        'approval_status' => 'حالة الموافقة',
        'shipper_user_id' => 'المندوب',
        'client_user_id' => 'العميل',
        'allow_open' => 'السماح بالفتح',
        'registered_at' => 'تاريخ التسجيل',
        'governorate' => 'المحافظة',
        'city' => 'المدينة',
        'shipper' => 'المندوب',
        'client' => 'العميل',
        'shippingContent' => 'محتوى الشحنة',
        'order_note' => 'ملاحظة الأوردر',
        'is_in_shipper_collection' => 'في تحصيل المندوب',
        'is_shipper_collected' => 'تم تحصيل المندوب',
        'shipper_collected_at' => 'تاريخ تحصيل المندوب',
        'is_in_client_settlement' => 'في تسوية العميل',
        'is_client_settled' => 'تم تسوية العميل',
        'client_settled_at' => 'تاريخ تسوية العميل',
        'is_in_shipper_return' => 'في مرتجع المندوب',
        'is_shipper_returned' => 'تم رجوع المندوب',
        'shipper_returned_at' => 'تاريخ رجوع المندوب',
        'is_in_client_return' => 'في مرتجع العميل',
        'is_client_returned' => 'تم رجوع العميل',
        'client_returned_at' => 'تاريخ رجوع العميل',
        'has_return' => 'المرتجع',
        'has_return_at' => 'تاريخ المرتجع',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
    ];

    public const VIEW_COLUMNS = [
        'id' => null,
        'code' => 'order.column.code.view',
        'external_code' => 'order.column.external_code.view',
        'receiver_name' => 'order.column.receiver_name.view',
        'phone' => 'order.column.phone.view',
        'phone_2' => 'order.column.phone_2.view',
        'address' => 'order.column.address.view',
        'governorate_id' => 'order.column.governorate_id.view',
        'city_id' => 'order.column.city_id.view',
        'total_amount' => 'order.column.total_amount.view',
        'shipping_fee' => 'order.column.shipping_fee.view',
        'commission_amount' => 'order.column.commission_amount.view',
        'company_amount' => 'order.column.company_amount.view',
        'cod_amount' => 'order.column.cod_amount.view',
        'status' => 'order.column.status.view',
        'latest_status_note' => 'order.column.latest_status_note.view',
        'shipper_date' => 'order.column.shipper_date.view',
        'approval_status' => 'order.column.approval_status.view',
        'shipper_user_id' => 'order.column.shipper_user_id.view',
        'client_user_id' => 'order.column.client_user_id.view',
        'allow_open' => 'order.column.allow_open.view',
        'registered_at' => 'order.column.registered_at.view',
        'governorate' => 'order.column.governorate_id.view',
        'city' => 'order.column.city_id.view',
        'shipper' => 'order.column.shipper_user_id.view',
        'client' => 'order.column.client_user_id.view',
        'shippingContent' => 'order.column.shipping_content_id.view',
        'order_note' => 'order.column.order_note.view',
        'is_in_shipper_collection' => 'order.column.is_in_shipper_collection.view',
        'is_shipper_collected' => 'order.column.is_shipper_collected.view',
        'shipper_collected_at' => 'order.column.shipper_collected_at.view',
        'is_in_client_settlement' => 'order.column.is_in_client_settlement.view',
        'is_client_settled' => 'order.column.is_client_settled.view',
        'client_settled_at' => 'order.column.client_settled_at.view',
        'is_in_shipper_return' => 'order.column.is_in_shipper_return.view',
        'is_shipper_returned' => 'order.column.is_shipper_returned.view',
        'shipper_returned_at' => 'order.column.shipper_returned_at.view',
        'is_in_client_return' => 'order.column.is_in_client_return.view',
        'is_client_returned' => 'order.column.is_client_returned.view',
        'client_returned_at' => 'order.column.client_returned_at.view',
        'created_at' => 'order.column.created_at.view',
        'updated_at' => 'order.column.updated_at.view',
        'has_return' => null,
        'has_return_at' => null,
        'refused_reasons' => null,
    ];

    public const EDIT_COLUMNS = [
        'external_code' => 'order.column.external_code.edit',
        'receiver_name' => 'order.column.receiver_name.edit',
        'phone' => 'order.column.phone.edit',
        'address' => 'order.column.address.edit',
        'governorate_id' => 'order.column.governorate_id.edit',
        'city_id' => 'order.column.city_id.edit',
        'shipper_user_id' => 'order.column.shipper_user_id.edit',
        'total_amount' => 'order.column.total_amount.edit',
        'shipping_fee' => 'order.column.shipping_fee.edit',
        'status' => 'order.column.status.edit',
        'allow_open' => 'order.column.allow_open.edit',
        'latest_status_note' => 'order.column.latest_status_note.edit',
        'order_note' => 'order.column.order_note.edit',
    ];

    public static function allPermissionDefinitions(): array
    {
        $permissions = [
            ...self::PAGE_PERMISSIONS,
            ...self::ACTION_PERMISSIONS,
        ];

        foreach (self::VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'orders',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column),
                'type' => 'column',
            ];
        }

        foreach (self::EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'orders',
                'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column),
                'type' => 'column',
            ];
        }

        return $permissions;
    }
}
