export default [
  { heading: 'Home' },
  {
    title: 'Operations',
    icon: { icon: 'solar:delivery-bold-duotone' },
    children: [
      { title: 'Order List', to: 'apps-orders', action: 'manage', subject: 'order.page' },
      { title: 'Scan Orders', to: 'apps-orders-scan', action: 'manage', subject: 'order.scan.page' },
      { title: 'Approval Requests', to: 'apps-orders-approval-requests', action: 'manage', subject: 'order.approval.page' },
    ],
  },
  {
    title: 'Tools & Filters',
    icon: { icon: 'solar:filter-bold-duotone' },
    children: [
      { title: 'HOLD & Out For Delivery', to: 'apps-orders-hold-outfordelivery', action: 'manage', subject: 'order.page' },
      { title: 'Uncollected Client', to: 'apps-orders-uncollectedclient', action: 'manage', subject: 'order.page' },
      { title: 'Uncollected Shipper', to: 'apps-orders-uncollectedshipper', action: 'manage', subject: 'order.page' },
      { title: 'Unreturn Shipper', to: 'apps-orders-unreturnshipper', action: 'manage', subject: 'order.page' },
      { title: 'Unreturn Client', to: 'apps-orders-unreturnclient', action: 'manage', subject: 'order.page' },
      { title: 'Rejected Orders', to: 'apps-orders-rejected', action: 'manage', subject: 'order.page' },
      { title: 'Deleted (Trash)', to: 'apps-orders-deleted', action: 'manage', subject: 'order.page' },
    ],
  },
  {
    title: 'Logistics',
    icon: { icon: 'solar:box-bold-duotone' },
    children: [
      { title: 'Pickups', to: 'apps-operations-pickups', action: 'manage', subject: 'pickup-request.page' },
      { title: 'Visits', to: 'apps-operations-visits', action: 'manage', subject: 'visit.page' },
      { title: 'Materials', to: 'apps-operations-materials', action: 'manage', subject: 'material.page' },
      { title: 'Material Requests', to: 'apps-operations-material-requests', action: 'manage', subject: 'material-request.page' },
    ],
  },
  {
    title: 'Financials',
    icon: { icon: 'solar:dollar-minimalistic-bold-duotone' },
    children: [
      { title: 'Shipper Collections', to: 'apps-orders-shipper-collections', action: 'manage', subject: 'shipper-collection.page' },
      { title: 'Shipper Returns', to: 'apps-orders-shipper-returns', action: 'manage', subject: 'shipper-return.page' },
      { title: 'Client Settlements', to: 'apps-orders-client-settlements', action: 'manage', subject: 'client-settlement.page' },
      { title: 'Client Returns', to: 'apps-orders-client-returns', action: 'manage', subject: 'client-return.page' },
      { title: 'Expense List', to: 'expenses', action: 'manage', subject: 'expense.page' },
      { title: 'Expense categories', to: 'expense-categories', action: 'manage', subject: 'expense-category.page' },
    ],
  },
  {
    title: 'Users & CRM',
    icon: { icon: 'solar:users-group-rounded-bold-duotone' },
    children: [
      { title: 'Clients', to: 'apps-user-clients', action: 'manage', subject: 'client.page' },
      { title: 'Shippers', to: 'apps-user-shippers', action: 'manage', subject: 'shipper.page' },
      { title: 'Staff Users', to: 'apps-user-list', action: 'manage', subject: 'user.page' },
    ],
  },
  {
    title: 'Management',
    icon: { icon: 'solar:settings-bold-duotone' },
    children: [
      { title: 'Areas', to: 'apps-area', action: 'manage', subject: 'area.page' },
      { title: 'Shipping Content', to: 'apps-content', action: 'manage', subject: 'content.page' },
      { title: 'Shipping Plans', to: 'apps-plan', action: 'manage', subject: 'plan.page' },
      { title: 'Refused Reasons', to: 'apps-refused-reason', action: 'manage', subject: 'refused-reason.page' },
    ],
  },
  {
    title: 'System Settings',
    icon: { icon: 'solar:lock-bold-duotone' },
    children: [
      { title: 'Settings', to: 'apps-settings', action: 'manage', subject: 'setting.page' },
      { title: 'Roles & Permissions', to: 'apps-roles', action: 'manage', subject: 'user.page' },
      { title: 'Activity Logs', to: 'apps-activity-logs', action: 'manage', subject: 'activity-log.page' },
    ],
  },
]
