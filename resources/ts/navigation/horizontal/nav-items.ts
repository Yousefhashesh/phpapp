import type { HorizontalNavItems } from '@layouts/types'

export default [
  {
    title: 'Dashboard',
    icon: { icon: 'tabler-layout-dashboard' },
    to: 'dashboards-orders',
    action: 'manage',
    subject: 'order.dashboard.page',
  },
  {
    title: 'Orders Management',
    icon: { icon: 'tabler-truck' },
    children: [
      { title: 'Order List', to: 'apps-orders', action: 'manage', subject: 'order.page' },
      { title: 'Scan Orders', to: 'apps-orders-scan', action: 'manage', subject: 'order.scan.page' },
      { title: 'Approval Requests', to: 'apps-orders-approval-requests', action: 'manage', subject: 'order.approval.page' },

      {
        title: 'Special Filters',
        icon: { icon: 'tabler-filter' },
        children: [
          { title: 'HOLD & Out For Delivery', to: 'apps-orders-hold-outfordelivery', action: 'manage', subject: 'order.page' },
          { title: 'Uncollected Client', to: 'apps-orders-uncollectedclient', action: 'manage', subject: 'order.page' },
          { title: 'Uncollected Shipper', to: 'apps-orders-uncollectedshipper', action: 'manage', subject: 'order.page' },
          { title: 'Unreturn Shipper', to: 'apps-orders-unreturnshipper', action: 'manage', subject: 'order.page' },
          { title: 'Unreturn Client', to: 'apps-orders-unreturnclient', action: 'manage', subject: 'order.page' },
          { title: 'Rejected Orders', to: 'apps-orders-rejected', action: 'manage', subject: 'order.page' },
          { title: 'Deleted Orders (Trash)', to: 'apps-orders-deleted', action: 'manage', subject: 'order.page' },
        ],
      },
    ],
  },
  {
    title: 'Accounting',
    icon: { icon: 'tabler-currency-dollar' },
    children: [
      { title: 'Shipper Collections', to: 'apps-orders-shipper-collections', action: 'manage', subject: 'shipper-collection.page' },
      { title: 'Shipper Returns', to: 'apps-orders-shipper-returns', action: 'manage', subject: 'shipper-return.page' },
      { title: 'Client Settlements', to: 'apps-orders-client-settlements', action: 'manage', subject: 'client-settlement.page' },
      { title: 'Client Returns', to: 'apps-orders-client-returns', action: 'manage', subject: 'client-return.page' },
      { title: 'Expenses', to: 'expenses', action: 'manage', subject: 'expense.page' },
      { title: 'Categories', to: 'expense-categories', action: 'manage', subject: 'expense-category.page' },
    ],
  },
  {
    title: 'Logistics',
    icon: { icon: 'tabler-box' },
    children: [
      { title: 'Pickups', to: 'apps-operations-pickups', action: 'manage', subject: 'pickup-request.page' },
      { title: 'Visits', to: 'apps-operations-visits', action: 'manage', subject: 'visit.page' },
      { title: 'Materials', to: 'apps-operations-materials', action: 'manage', subject: 'material.page' },
      { title: 'Material Requests', to: 'apps-operations-material-requests', action: 'manage', subject: 'material-request.page' },
    ],
  },
  {
    title: 'Users & Core',
    icon: { icon: 'tabler-users' },
    children: [
      { title: 'Clients', to: 'apps-user-clients', action: 'manage', subject: 'client.page' },
      { title: 'Shippers', to: 'apps-user-shippers', action: 'manage', subject: 'shipper.page' },
      { title: 'User List', to: 'apps-user-list', action: 'manage', subject: 'user.page' },
      { title: 'System Settings', to: 'apps-settings', action: 'manage', subject: 'setting.page' },
      { title: 'Areas Control', to: 'apps-area', action: 'manage', subject: 'area.page' },
    ],
  },
  {
    title: 'Admin Tools',
    icon: { icon: 'tabler-lock' },
    children: [
      { title: 'Refused Reasons', to: 'apps-refused-reason', action: 'manage', subject: 'refused-reason.page' },
      { title: 'Shipping Content', to: 'apps-content', action: 'manage', subject: 'content.page' },
      { title: 'Shipping Plans', to: 'apps-plan', action: 'manage', subject: 'plan.page' },
      { title: 'Roles', to: 'apps-roles', action: 'manage', subject: 'user.page' },
      { title: 'Permissions', to: 'apps-permissions', action: 'manage', subject: 'user.page' },
      { title: 'Activity Logs', to: 'apps-activity-logs', action: 'manage', subject: 'activity-log.page' },
    ],
  },
] as HorizontalNavItems
