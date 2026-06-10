<?php

namespace App\Support\Permissions;

class DashboardPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'order.dashboard.page', 'group' => 'dashboard', 'label' => 'دخول صفحة لوحة التحكم (الداشبورد)', 'type' => 'page'],
        ['name' => 'order.dashboard.view', 'group' => 'dashboard', 'label' => 'عرض بيانات لوحة التحكم', 'type' => 'action'],
    ];

    private const CARD_NAMES_AR = [
        'all_order' => 'كل الطلبات',
        'out_for_delivery' => 'قيد التسليم',
        'hold' => 'انتظار',
        'delivered' => 'تم التسليم',
        'undelivered' => 'لم يتم التسليم',
        'uncollected_shipper' => 'غير محصل من المندوب',
        'collected_shipper' => 'تم تحصيل المندوب',
        'unreturn_shipper' => 'غير مرتجع من المندوب',
        'return_shipper' => 'تم مرتجع المندوب',
        'uncollected_client' => 'غير محصل للعميل',
        'collected_client' => 'تم تحصيل العميل',
        'return_client' => 'تم مرتجع العميل',
        'unreturn_client' => 'غير مرتجع للعميل',
        'out_for_delivery_total' => 'إجمالي مبالغ قيد التسليم',
        'hold_total' => 'إجمالي مبالغ الانتظار',
        'delivered_total' => 'إجمالي مبالغ تم التسليم',
        'undelivered_total' => 'إجمالي مبالغ لم يتم التسليم',
        'uncollected_shipper_total' => 'إجمالي غير محصل مندوب (مبالغ)',
        'collected_shipper_total' => 'إجمالي تم تحصيل مندوب (مبالغ)',
        'unreturn_shipper_total' => 'إجمالي غير مرتجع مندوب (مبالغ)',
        'return_shipper_total' => 'إجمالي تم مرتجع مندوب (مبالغ)',
        'uncollected_client_total' => 'إجمالي غير محصل عميل (مبالغ)',
        'collected_client_total' => 'إجمالي تم تحصيل عميل (مبالغ)',
        'cash_ready' => 'كاش جاهز',
        'net' => 'الصافي',
        'return_client_total' => 'إجمالي تم مرتجع عميل (مبالغ)',
        'unreturn_client_total' => 'إجمالي غير مرتجع عميل (مبالغ)',
        'total_fees' => 'إجمالي الرسوم',
        'total_shipper_fees' => 'إجمالي عمولات المناديب',
        'total_cop' => 'إجمالي COP',
        'total_expenses' => 'إجمالي المصاريف',
        'total_revenue' => 'إجمالي الإيرادات',
    ];

    private const CHART_NAMES_AR = [
        'financial' => 'الرسم البياني المالي',
        'status_donut' => 'توزيع الحالات (دونات)',
        'count_breakdown' => 'تحليل الأعداد',
        'collection_rates' => 'معدلات التحصيل',
        'top_governorates' => 'أفضل المحافظات',
    ];

    public const CARD_VIEW_PERMISSIONS = [
        'all_order' => 'order.dashboard.card.all_order.view',
        'out_for_delivery' => 'order.dashboard.card.out_for_delivery.view',
        'hold' => 'order.dashboard.card.hold.view',
        'delivered' => 'order.dashboard.card.delivered.view',
        'undelivered' => 'order.dashboard.card.undelivered.view',
        'uncollected_shipper' => 'order.dashboard.card.uncollected_shipper.view',
        'collected_shipper' => 'order.dashboard.card.collected_shipper.view',
        'unreturn_shipper' => 'order.dashboard.card.unreturn_shipper.view',
        'return_shipper' => 'order.dashboard.card.return_shipper.view',
        'uncollected_client' => 'order.dashboard.card.uncollected_client.view',
        'collected_client' => 'order.dashboard.card.collected_client.view',
        'return_client' => 'order.dashboard.card.return_client.view',
        'unreturn_client' => 'order.dashboard.card.unreturn_client.view',
        'out_for_delivery_total' => 'order.dashboard.card.out_for_delivery_total.view',
        'hold_total' => 'order.dashboard.card.hold_total.view',
        'delivered_total' => 'order.dashboard.card.delivered_total.view',
        'undelivered_total' => 'order.dashboard.card.undelivered_total.view',
        'uncollected_shipper_total' => 'order.dashboard.card.uncollected_shipper_total.view',
        'collected_shipper_total' => 'order.dashboard.card.collected_shipper_total.view',
        'unreturn_shipper_total' => 'order.dashboard.card.unreturn_shipper_total.view',
        'return_shipper_total' => 'order.dashboard.card.return_shipper_total.view',
        'uncollected_client_total' => 'order.dashboard.card.uncollected_client_total.view',
        'collected_client_total' => 'order.dashboard.card.collected_client_total.view',
        'cash_ready' => 'order.dashboard.card.cash_ready.view',
        'net' => 'order.dashboard.card.net.view',
        'return_client_total' => 'order.dashboard.card.return_client_total.view',
        'unreturn_client_total' => 'order.dashboard.card.unreturn_client_total.view',
        'total_fees' => 'order.dashboard.card.total_fees.view',
        'total_shipper_fees' => 'order.dashboard.card.total_shipper_fees.view',
        'total_cop' => 'order.dashboard.card.total_cop.view',
        'total_expenses' => 'order.dashboard.card.total_expenses.view',
        'total_revenue' => 'order.dashboard.card.total_revenue.view',
    ];

    public const CHART_VIEW_PERMISSIONS = [
        'financial' => 'order.dashboard.chart.financial.view',
        'status_donut' => 'order.dashboard.chart.status_donut.view',
        'count_breakdown' => 'order.dashboard.chart.count_breakdown.view',
        'collection_rates' => 'order.dashboard.chart.collection_rates.view',
        'top_governorates' => 'order.dashboard.chart.top_governorates.view',
    ];

    public static function allPermissionDefinitions(): array
    {
        $permissions = [
            ...self::PAGE_PERMISSIONS,
        ];

        foreach (self::CARD_VIEW_PERMISSIONS as $cardKey => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'dashboard',
                'label' => 'عرض بطاقة ' . (self::CARD_NAMES_AR[$cardKey] ?? $cardKey) . ' في الداشبورد',
                'type' => 'card',
            ];
        }

        foreach (self::CHART_VIEW_PERMISSIONS as $chartKey => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'dashboard',
                'label' => 'عرض رسم بياني ' . (self::CHART_NAMES_AR[$chartKey] ?? $chartKey) . ' في الداشبورد',
                'type' => 'chart',
            ];
        }

        return $permissions;
    }
}
