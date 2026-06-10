export function normalizeFinancialOrder(order: Record<string, any>) {
  return {
    ...order,
    client: order.client ?? {
      name: order.client_name ?? '—',
      phone: order.client_phone ?? '',
    },
  }
}

export function normalizeFinancialOrders(orders: Record<string, any>[]) {
  return orders.map(normalizeFinancialOrder)
}

export function formatFinancialMoney(value: number | string | null | undefined) {
  const amount = Number(value)

  return `EGP ${Number.isFinite(amount) ? amount.toFixed(2) : '0.00'}`
}

export function summarizeFinancialOrders(orders: Record<string, any>[]) {
  let totalAmount = 0
  let totalFees = 0
  let totalCod = 0

  for (const order of orders) {
    totalAmount += Number(order.total_amount) || 0
    totalFees += Number(order.shipping_fee) || 0
    totalCod += Number(order.cod_amount) || 0
  }

  return {
    totalAmount,
    totalFees,
    totalCod,
    count: orders.length,
  }
}

export function resolveSelectedOrderRows(selectedIds: Array<number | string>, orders: Record<string, any>[]) {
  const idSet = new Set(selectedIds.map(id => Number(id)))

  return orders.filter(order => idSet.has(Number(order.id)))
}
