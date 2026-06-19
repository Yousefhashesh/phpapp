export function isOrderFinanciallyLocked(order: any): boolean {
  if (!order)
    return false

  return Boolean(
    order.is_shipper_collected
    || order.is_client_settled
    || order.is_shipper_returned
    || order.is_client_returned,
  )
}

export const ACTIVE_DELIVERY_STATUSES = ['OUT_FOR_DELIVERY', 'HOLD'] as const

export function isActiveDeliveryStatus(status: string): boolean {
  return ACTIVE_DELIVERY_STATUSES.includes(status as typeof ACTIVE_DELIVERY_STATUSES[number])
}
