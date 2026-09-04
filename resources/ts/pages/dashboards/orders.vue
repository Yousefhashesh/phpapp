<script setup lang="ts">
import CardWidgetsEarningReportsYearlyOverview from '@/views/pages/cards/card-widgets/CardWidgetsEarningReportsYearlyOverview.vue'
import { hexToRgb } from '@core/utils/colorConverter'
import { useRouter } from 'vue-router'
import { useTheme } from 'vuetify'

const { can } = useAbility()

definePage({
  meta: {
    action: 'manage',
    subject: 'order.dashboard.page',
  },
})

type CardValue = number | string

interface DashboardOrdersResponse {
  lifecycle: Record<string, number>
  approval: Record<string, number>
  progress: Record<string, number>
  financial: Record<string, number>
  charts?: {
    top_governorates?: Array<{
      name: string
      total_orders: number
    }>
  }
}

interface CardMeta {
  title: string
  icon: string
  color: string
  isMoney?: boolean
}

const vuetifyTheme = useTheme()


const cardMeta: Record<string, CardMeta & { permission?: string }> = {
  all_order: { title: 'Total Orders', icon: 'tabler-package', color: 'primary', permission: 'order.dashboard.card.all_order.view' },
  out_for_delivery: { title: 'Out For Delivery', icon: 'tabler-truck-delivery', color: 'info', permission: 'order.dashboard.card.out_for_delivery.view' },
  hold: { title: 'On Hold', icon: 'tabler-pause-circle', color: 'warning', permission: 'order.dashboard.card.hold.view' },
  delivered: { title: 'Delivered', icon: 'tabler-circle-check', color: 'success', permission: 'order.dashboard.card.delivered.view' },
  undelivered: { title: 'Undelivered', icon: 'tabler-alert-circle', color: 'error', permission: 'order.dashboard.card.undelivered.view' },

  out_for_delivery_total: { title: 'Out For Delivery Total', icon: 'tabler-truck-delivery', color: 'info', isMoney: true, permission: 'order.dashboard.card.out_for_delivery_total.view' },
  hold_total: { title: 'Hold Total', icon: 'tabler-pause-circle', color: 'warning', isMoney: true, permission: 'order.dashboard.card.hold_total.view' },
  undelivered_total: { title: 'Undelivered Total', icon: 'tabler-alert-circle', color: 'error', isMoney: true, permission: 'order.dashboard.card.undelivered_total.view' },

  pending_approval: { title: 'Pending Approval', icon: 'tabler-clipboard-check', color: 'warning' },
  approved: { title: 'Approved', icon: 'tabler-check', color: 'success' },
  rejected: { title: 'Rejected', icon: 'tabler-x', color: 'error' },

  collected_shipper: { title: 'Collected (Shipper)', icon: 'tabler-box', color: 'success', permission: 'order.dashboard.card.collected_shipper.view' },
  uncollected_shipper: { title: 'Uncollected Shipper', icon: 'tabler-box-off', color: 'error', permission: 'order.dashboard.card.uncollected_shipper.view' },

  settled_client: { title: 'Settled Client', icon: 'tabler-receipt', color: 'success', permission: 'order.dashboard.card.collected_client.view' },
  uncollected_client: { title: 'Uncollected Client', icon: 'tabler-receipt', color: 'error', permission: 'order.dashboard.card.uncollected_client.view' },

  return_shipper: { title: 'Returned By Shipper', icon: 'tabler-arrow-back', color: 'success', permission: 'order.dashboard.card.return_shipper.view' },
  unreturn_shipper: { title: 'Unreturn Shipper', icon: 'tabler-arrow-back-up', color: 'warning', permission: 'order.dashboard.card.unreturn_shipper.view' },

  return_client: { title: 'Client Returned', icon: 'tabler-rotate', color: 'info', permission: 'order.dashboard.card.return_client.view' },
  unreturn_client: { title: 'Unreturn Client', icon: 'tabler-rotate-2', color: 'warning', permission: 'order.dashboard.card.unreturn_client.view' },

  delivered_total: { title: 'Delivered Amount', icon: 'tabler-cash-banknote', color: 'success', isMoney: true, permission: 'order.dashboard.card.delivered_total.view' },
  cash_ready: { title: 'كاش جاهز (للعميل)', icon: 'tabler-coins', color: 'info', isMoney: true, permission: 'order.dashboard.card.cash_ready.view' },
  net: { title: 'صافي معلق (للمندوب)', icon: 'tabler-scale', color: 'warning', isMoney: true, permission: 'order.dashboard.card.net.view' },
  total_fees: { title: 'Total Fees', icon: 'tabler-receipt-2', color: 'secondary', isMoney: true, permission: 'order.dashboard.card.total_fees.view' },
  total_shipper_fees: { title: 'Total Shipper Fees', icon: 'tabler-user-dollar', color: 'secondary', isMoney: true, permission: 'order.dashboard.card.total_shipper_fees.view' },
  total_cop: { title: 'Total COP', icon: 'tabler-building-bank', color: 'primary', isMoney: true, permission: 'order.dashboard.card.total_cop.view' },
  total_cod: { title: 'Total COD Amount', icon: 'tabler-cash', color: 'info', isMoney: true },
  total_expenses: { title: 'Total Expenses', icon: 'tabler-credit-card', color: 'error', isMoney: true, permission: 'order.dashboard.card.total_expenses.view' },
  total_revenue: { title: 'Total Revenue', icon: 'tabler-chart-line', color: 'success', isMoney: true, permission: 'order.dashboard.card.total_revenue.view' },

  collected_shipper_total: { title: 'Collected Shipper Total', icon: 'tabler-cash', color: 'success', isMoney: true, permission: 'order.dashboard.card.collected_shipper_total.view' },
  uncollected_shipper_total: { title: 'Uncollected Shipper Total', icon: 'tabler-cash-off', color: 'error', isMoney: true, permission: 'order.dashboard.card.uncollected_shipper_total.view' },
  collected_client_total: { title: 'Collected Client Total', icon: 'tabler-cash', color: 'success', isMoney: true, permission: 'order.dashboard.card.collected_client_total.view' },
  uncollected_client_total: { title: 'Uncollected Client Total', icon: 'tabler-cash-off', color: 'error', isMoney: true, permission: 'order.dashboard.card.uncollected_client_total.view' },
  return_shipper_total: { title: 'Return Shipper Total', icon: 'tabler-arrow-back-up', color: 'warning', isMoney: true, permission: 'order.dashboard.card.return_shipper_total.view' },
  unreturn_shipper_total: { title: 'Unreturn Shipper Total', icon: 'tabler-arrow-back', color: 'error', isMoney: true, permission: 'order.dashboard.card.unreturn_shipper_total.view' },
  return_client_total: { title: 'Return Client Total', icon: 'tabler-rotate', color: 'warning', isMoney: true, permission: 'order.dashboard.card.return_client_total.view' },
  unreturn_client_total: { title: 'Unreturn Client Total', icon: 'tabler-rotate-2', color: 'error', isMoney: true, permission: 'order.dashboard.card.unreturn_client_total.view' },
}

// const cardOrder = [
//   'all_order', 'out_for_delivery', 'hold', 'delivered', 'undelivered',
//   'pending_approval', 'approved', 'rejected',
//   'collected_shipper', 'settled_client', 'return_shipper', 'return_client',
//   'cash_ready', 'net', 'total_shipper_fees', 'total_cop', 'total_expenses', 'total_revenue',
// ]



const cardOrder = [
  'all_order', 'out_for_delivery', 'hold', 'delivered', 'undelivered',
  'pending_approval', 'approved', 'rejected',
  'collected_shipper', 'uncollected_shipper',
  'settled_client', 'uncollected_client',
  'return_shipper', 'unreturn_shipper',
  'return_client', 'unreturn_client',
  'cash_ready', 'net', 'total_shipper_fees', 'total_cop', 'total_expenses', 'total_revenue',
]

const { data, error, isFetching, execute } = await useApi<DashboardOrdersResponse>('/dashboard/orders')

const payload = computed(() => {
  if (!data.value) return {}
  
  const l = data.value.lifecycle ?? {}
  const a = data.value.approval ?? {}
  const p = data.value.progress ?? {}
  const f = data.value.financial ?? {}
  
  return {
    // Lifecycle Counts
    all_order: l.total ?? 0,
    out_for_delivery: l.out_for_delivery ?? 0,
    delivered: l.delivered ?? 0,
    hold: l.hold ?? 0,
    undelivered: l.undelivered ?? 0,
    
    // Lifecycle Amounts
    out_for_delivery_total: l.out_for_delivery_amount ?? 0,
    hold_total: l.hold_amount ?? 0,
    delivered_total: l.delivered_amount ?? 0,
    undelivered_total: l.undelivered_amount ?? 0,

    // Approval
    pending_approval: a.pending ?? 0,
    approved: a.approved ?? 0,
    rejected: a.rejected ?? 0,
    
    // Progress Counts
    collected_shipper: p.shipper_collected ?? 0,
    uncollected_shipper: p.uncollected_shipper ?? 0,
    settled_client: p.client_settled ?? 0,
    collected_client: p.client_settled ?? 0, // Alias
    uncollected_client: p.uncollected_client ?? 0,
    return_shipper: p.shipper_returned ?? 0,
    unreturn_shipper: p.unreturn_shipper ?? 0,
    return_client: p.client_returned ?? 0,
    unreturn_client: p.unreturn_client ?? 0,
    
    // Progress Amounts (mapped from financial)
    collected_shipper_total: f.collected_cod ?? 0,
    uncollected_shipper_total: f.uncollected_shipper_cod ?? 0,
    collected_client_total: f.settled_cod ?? 0,
    uncollected_client_total: f.unsettled_client_cod ?? 0,
    
    // Financial Totals
    total_amount: f.total_amount ?? 0,
    total_fees: f.shipping_fee ?? 0,
    total_shipper_fees: f.commission ?? 0,
    total_cop: f.company_net ?? 0,
    cash_ready: f.uncollected_shipper_cod ?? 0,
    net: f.shipper_net_pending ?? 0,
    total_cod: f.total_cod ?? 0,
    total_expenses: f.total_expenses ?? 0,
    total_revenue: f.total_revenue ?? ((f.collected_company_net ?? 0) - (f.total_expenses ?? 0)),

    // Placeholders for amounts not directly in summary yet but expected by UI
    return_shipper_total: 0,
    unreturn_shipper_total: 0,
    return_client_total: 0,
    unreturn_client_total: 0,
  }
})

const router = useRouter()

const cardRoutes: Record<string, string> = {
  all_order: '/apps/orders',
  out_for_delivery: '/apps/orders/hold-outfordelivery?status=OUT_FOR_DELIVERY',
  hold: '/apps/orders/hold-outfordelivery?status=HOLD',
  delivered: '/apps/orders?status=DELIVERED',
  undelivered: '/apps/orders?status=UNDELIVERED',
  
  pending_approval: '/apps/orders/approval-requests',
  rejected: '/apps/orders/rejected',
  
  collected_shipper: '/apps/orders/shipper-collections',
  uncollected_shipper: '/apps/orders/uncollectedshipper',
  settled_client: '/apps/orders/client-settlements',
  uncollected_client: '/apps/orders/uncollectedclient',
  return_shipper: '/apps/orders/shipper-returns',
  unreturn_shipper: '/apps/orders/unreturnshipper',
  return_client: '/apps/orders/client-returns',
  unreturn_client: '/apps/orders/unreturnclient',
  delivered_total: '/apps/orders?status=DELIVERED',
  cash_ready: '/apps/orders/uncollectedshipper',
  net: '/apps/orders/uncollectedshipper',
}

const goToRoute = (key: string) => {
  const route = cardRoutes[key]
  if (route) router.push(route)
}

const isMoneyKey = (key: string) => cardMeta[key]?.isMoney || key.endsWith('_total') || key.endsWith('_sum') || ['cash_ready', 'net', 'total_fees', 'total_shipper_fees', 'total_cop'].includes(key)

const toNumber = (v: CardValue | undefined) => {
  const num = Number(v)
  return Number.isFinite(num) ? num : 0
}

const formatMoney = (v: number) =>
  `${new Intl.NumberFormat('en-US', { maximumFractionDigits: 2, minimumFractionDigits: 2 }).format(v)} EGP`

const formatStat = (key: string, value: CardValue) => {
  const numericValue = toNumber(value)
  if (isMoneyKey(key))
    return formatMoney(numericValue)
  return new Intl.NumberFormat('en-US').format(numericValue)
}

const cards = computed(() => {
  return Object.entries(payload.value)
    .sort(([a], [b]) => {
      const ai = cardOrder.indexOf(a)
      const bi = cardOrder.indexOf(b)
      if (ai === -1 && bi === -1) return a.localeCompare(b)
      if (ai === -1) return 1
      if (bi === -1) return -1
      return ai - bi
    })
    .filter(([key]) => {
      const meta = cardMeta[key]
      return !meta || !meta.permission || can(meta.permission as any, 'all' as any)
    })
    .map(([key, value]) => {
      const meta = cardMeta[key] ?? {
        title: key.replaceAll('_', ' ').replace(/\b\w/g, ch => ch.toUpperCase()),
        icon: 'tabler-chart-bar',
        color: 'primary',
      }
      return { key, title: meta.title, icon: meta.icon, color: meta.color, stat: formatStat(key, value), isMoney: isMoneyKey(key) }
    })
})

const countCards = computed(() => cards.value.filter(c => !c.isMoney))
const moneyCards = computed(() => cards.value.filter(c => c.isMoney))

// KPI top 4 highlight cards
const kpiCards = computed(() => [
  {
    key: 'all_order',
    title: 'Total Orders',
    icon: 'tabler-package',
    color: 'primary',
    stat: formatStat('all_order', payload.value.all_order ?? 0),
    permission: 'order.dashboard.card.all_order.view',
  },
  {
    key: 'total_revenue',
    title: 'إجمالي الإيرادات',
    icon: 'tabler-chart-line',
    color: 'success',
    stat: formatStat('total_revenue', payload.value.total_revenue ?? 0),
    permission: 'order.dashboard.card.total_revenue.view',
  },
  {
    key: 'cash_ready',
    title: 'كاش جاهز (للعميل)',
    icon: 'tabler-coins',
    color: 'info',
    stat: formatStat('cash_ready', payload.value.cash_ready ?? 0),
    permission: 'order.dashboard.card.cash_ready.view',
  },
  {
    key: 'net',
    title: 'صافي معلق (للمندوب)',
    icon: 'tabler-scale',
    color: 'warning',
    stat: formatStat('net', payload.value.net ?? 0),
    permission: 'order.dashboard.card.net.view',
  },
].filter(c => !c.permission || can(c.permission as any, 'all' as any)))

// ─── Chart helpers ──────────────────────────────────────────────────────────
const themeColors = computed(() => vuetifyTheme.current.value)

const secondaryText = computed(() => {
  const c = themeColors.value
  return `rgba(${hexToRgb(c.colors['on-surface'])},${c.variables['medium-emphasis-opacity']})`
})

const primaryText = computed(() => {
  const c = themeColors.value
  return `rgba(${hexToRgb(c.colors['on-surface'])},${c.variables['high-emphasis-opacity']})`
})

const borderColor = computed(() => {
  const c = themeColors.value
  return `rgba(${hexToRgb(String(c.variables['border-color']))},${c.variables['border-opacity']})`
})

const disabledText = computed(() => {
  const c = themeColors.value
  return `rgba(${hexToRgb(c.colors['on-surface'])},${c.variables['disabled-opacity']})`
})

// ─── 1. Donut – Order Status Distribution ───────────────────────────────────
const donutSeries = computed(() => [
  toNumber(payload.value.out_for_delivery),
  toNumber(payload.value.hold),
  toNumber(payload.value.delivered),
  toNumber(payload.value.undelivered),
])

const donutConfig = computed(() => ({
  stroke: { width: 0 },
  labels: ['Out for Delivery', 'On Hold', 'Delivered', 'Undelivered'],
  colors: ['#26c6f9', '#ffab00', '#72e128', '#ff4c51'],
  dataLabels: {
    enabled: true,
    formatter: (val: number) => `${Math.round(val)}%`,
  },
  legend: {
    position: 'bottom',
    markers: { offsetX: -3 },
    fontSize: '13px',
    labels: { colors: secondaryText.value },
    itemMargin: { vertical: 3, horizontal: 10 },
  },
  plotOptions: {
    pie: {
      donut: {
        labels: {
          show: true,
          name: { fontSize: '1rem', color: secondaryText.value },
          value: {
            fontSize: '1.125rem',
            color: secondaryText.value,
            formatter: (val: string) => `${Number.parseInt(val, 10)}`,
          },
          total: {
            show: true,
            fontSize: '1rem',
            label: 'Total',
            color: primaryText.value,
            formatter: (w: any) => w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0).toString(),
          },
        },
      },
    },
  },
  chart: { toolbar: { show: false } },
  responsive: [{ breakpoint: 576, options: { chart: { height: 300 } } }],
}))

// ─── 2. Radial Bar – Collection & Settlement Rates ──────────────────────────
const radialSeries = computed(() => {
  const pct = (a: number, b: number) => (a + b === 0 ? 0 : Math.round((a / (a + b)) * 100))
  return [
    pct(toNumber(payload.value.collected_shipper), toNumber(payload.value.uncollected_shipper)),
    pct(toNumber(payload.value.collected_client), toNumber(payload.value.uncollected_client)),
    pct(toNumber(payload.value.uncollected_client), toNumber(payload.value.collected_client)),
    pct(toNumber(payload.value.uncollected_shipper), toNumber(payload.value.collected_shipper)),
    
  ]
})

const radialConfig = computed(() => ({
  stroke: { lineCap: 'round' },
  labels: ['Collected Shipper', 'Collected Client', 'UnCollected Shipper', 'UnCollected Client'],
  colors: ['#72e128', '#26c6f9', '#e52b50','#e32636'],
  legend: {
    show: true,
    fontSize: '13px',
    position: 'bottom',
    labels: { colors: secondaryText.value },
    markers: { offsetX: -3 },
    itemMargin: { vertical: 3, horizontal: 10 },
  },
  plotOptions: {
    radialBar: {
      hollow: { size: '28%' },
      track: {
        margin: 12,
        background: themeColors.value.variables['track-bg'],
      },
      dataLabels: {
        name: { fontSize: '1rem' },
        value: { fontSize: '0.9375rem', color: secondaryText.value },
        total: {
          show: true,
          fontSize: '1rem',
          label: 'Avg Rate',
          color: primaryText.value,
          formatter(w: any) {
            const avg = w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0) / w.globals.series.length
            return `${Math.round(avg)}%`
          },
        },
      },
    },
  },
  grid: { padding: { top: -20, bottom: -20 } },
  chart: { toolbar: { show: false } },
}))




// ─── 4. Column Chart – Financial Summary ────────────────────────────────────
const financialSeries = computed(() => [{
  name: 'Amount (EGP)',
  data: [
    toNumber(payload.value.delivered_total),
    toNumber(payload.value.total_fees),
    toNumber(payload.value.total_shipper_fees),
    toNumber(payload.value.total_cop),
    toNumber(payload.value.total_expenses),
    toNumber(payload.value.total_revenue),
  ],
}])

const financialConfig = computed(() => ({
  chart: { toolbar: { show: false }, parentHeightOffset: 0 },
  colors: ['#72e128', '#26c6f9', '#ff9f43', '#7367f0', '#ff4c51', '#28dac6'],
  plotOptions: {
    bar: {
      borderRadius: 6,
      columnWidth: '55%',
      distributed: true,
    },
  },
  dataLabels: { enabled: false },
  legend: { show: false },
  grid: {
    borderColor: borderColor.value,
    padding: { top: -10 },
    yaxis: { lines: { show: true } },
  },
  xaxis: {
    axisBorder: { show: false },
    axisTicks: { color: borderColor.value },
    categories: ['Delivered\nAmount', 'Fees', 'Shipper\nFees', 'COP', 'Expenses', 'Revenue'],
    labels: { style: { colors: disabledText.value, fontSize: '0.75rem' } },
  },
  yaxis: {
    labels: {
      style: { colors: disabledText.value, fontSize: '0.8125rem' },
      formatter: (v: number) => v >= 1000 ? `${(v / 1000).toFixed(0)}K` : v.toString(),
    },
  },
  tooltip: {
    y: { formatter: (v: number) => formatMoney(v) },
  },
}))

// ─── 5. Horizontal Bar – Top 7 Governorates by Shipping Orders ────────────
const topGovernorates = computed(() => data.value?.charts?.top_governorates ?? [])

const governoratesSeries = computed(() => [{
  name: 'Orders',
  data: topGovernorates.value.map(item => toNumber(item.total_orders)),
}])

const governoratesConfig = computed(() => ({
  chart: { toolbar: { show: false }, parentHeightOffset: 0 },
  colors: ['#7367f0'],
  plotOptions: {
    bar: {
      borderRadius: 6,
      barHeight: '56%',
      horizontal: true,
    },
  },
  dataLabels: {
    enabled: true,
    offsetX: 8,
    style: { fontSize: '11px' },
  },
  legend: { show: false },
  grid: {
    borderColor: borderColor.value,
    padding: { top: -8, bottom: 0 },
    xaxis: { lines: { show: true } },
  },
  xaxis: {
    axisBorder: { show: false },
    axisTicks: { color: borderColor.value },
    categories: topGovernorates.value.map(item => item.name),
    labels: { style: { colors: disabledText.value, fontSize: '0.8125rem' } },
  },
  yaxis: {
    labels: { style: { colors: disabledText.value, fontSize: '0.8125rem' } },
  },
  tooltip: {
    y: { formatter: (v: number) => `${v} orders` },
  },
}))

const yearlyOverviewReports = computed(() => ([
  {
    title: 'Orders',
    icon: 'tabler-package',
    categories: ['All', 'Out', 'Hold', 'Delivered', 'Undelivered', 'Ret Client', 'Ret Shipper'],
    data: [
      toNumber(payload.value.all_order),
      toNumber(payload.value.out_for_delivery),
      toNumber(payload.value.hold),
      toNumber(payload.value.delivered),
      toNumber(payload.value.undelivered),
      toNumber(payload.value.return_client),
      toNumber(payload.value.return_shipper),
    ],
    highlightIndex: 3,
  },
  {
    title: 'Collections',
    icon: 'tabler-wallet',
    categories: ['Shp Uncol', 'Shp Col', 'Cli Unset', 'Cli Set', 'Cash Ready', 'Net'],
    data: [
      toNumber(payload.value.uncollected_shipper_total),
      toNumber(payload.value.collected_shipper_total),
      toNumber(payload.value.uncollected_client_total),
      toNumber(payload.value.collected_client_total),
      toNumber(payload.value.cash_ready),
      toNumber(payload.value.net),
    ],
    highlightIndex: 4,
  },
  {
    title: 'Returns',
    icon: 'tabler-arrow-back-up',
    categories: ['Ret Shp Amt', 'Pending Shp Amt', 'Ret Cli COD', 'Pending Cli COD', 'Undelivered Amt'],
    data: [
      toNumber(payload.value.return_shipper_total),
      toNumber(payload.value.unreturn_shipper_total),
      toNumber(payload.value.return_client_total),
      toNumber(payload.value.unreturn_client_total),
      toNumber(payload.value.undelivered_total),
    ],
    highlightIndex: 0,
  },
  {
    title: 'Finance',
    icon: 'tabler-chart-line',
    categories: ['Delivered', 'Fees', 'Shipper Fees', 'Company', 'Expenses', 'Revenue'],
    data: [
      toNumber(payload.value.delivered_total),
      toNumber(payload.value.total_fees),
      toNumber(payload.value.total_shipper_fees),
      toNumber(payload.value.total_cop),
      toNumber(payload.value.total_expenses),
      toNumber(payload.value.total_revenue),
    ],
    highlightIndex: 5,
  },
]))

const refreshDashboard = () => execute()
</script>

<template>
  <VRow>
    <!-- ─── Page Header ─────────────────────────────────────── -->
    <VCol cols="12">
      <div class="d-flex align-center justify-space-between">
        <div>
          <h4 class="text-h4 mb-1">
            لوحة التحكم
          </h4>
          <span class="text-medium-emphasis text-sm">إحصائيات الطلبات والماليات لحظياً</span>
        </div>
        <VBtn
          icon
          variant="text"
          :loading="isFetching"
          @click="refreshDashboard"
        >
          <VIcon icon="tabler-refresh" />
        </VBtn>
      </div>
    </VCol>

    <!-- ─── Error ──────────────────────────────────────────── -->
    <VCol
      v-if="error"
      cols="12"
    >
      <VAlert
        type="error"
        variant="tonal"
      >
        Failed to load dashboard data. Check API auth/permissions.
      </VAlert>
    </VCol>

    <!-- ─── Skeleton ───────────────────────────────────────── -->
    <template v-if="isFetching && !cards.length">
      <VCol
        v-for="n in 4"
        :key="`skeleton-kpi-${n}`"
        cols="12"
        sm="6"
        md="3"
      >
        <VSkeletonLoader type="card" />
      </VCol>
      <VCol cols="12" md="7"><VSkeletonLoader type="image" /></VCol>
      <VCol cols="12" md="5"><VSkeletonLoader type="image" /></VCol>
      <VCol cols="12" md="5"><VSkeletonLoader type="image" /></VCol>
      <VCol cols="12" md="7"><VSkeletonLoader type="image" /></VCol>
    </template>

    <template v-if="cards.length">
      <!-- ─── KPI Row ──────────────────────────────────────── -->
      <VCol
        v-for="kpi in kpiCards"
        :key="kpi.key"
        cols="12"
        sm="6"
        md="3"
      >
        <VCard
          class="dash-stat-card cursor-pointer"
          :style="`--stat-color: var(--v-theme-${kpi.color})`"
          @click="goToRoute(kpi.key)"
        >
          <VCardText>
            <div class="d-flex align-center gap-x-4 mb-3">
              <VAvatar
                variant="tonal"
                :color="kpi.color"
                rounded
                size="50"
              >
                <VIcon :icon="kpi.icon" size="28" />
              </VAvatar>
              <h4 class="text-h4">
                {{ kpi.stat }}
              </h4>
            </div>
            <div class="text-body-1 text-medium-emphasis">
              {{ kpi.title }}
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- ─── Charts Row 1 ─────────────────────────────────── -->
      <!-- Financial Column Chart -->
      <VCol
        v-if="can('order.dashboard.chart.financial.view' as any, 'all' as any)"
        cols="12"
        md="7"
      >
        <VCard height="100%">
          <VCardItem>
            <VCardTitle>Financial Summary</VCardTitle>
            <VCardSubtitle>Amounts breakdown in EGP</VCardSubtitle>
          </VCardItem>
          <VCardText>
            <VueApexCharts
              type="bar"
              height="280"
              :options="financialConfig"
              :series="financialSeries"
            />
          </VCardText>
        </VCard>
      </VCol>

      <!-- Order Status Donut -->
      <VCol
        v-if="can('order.dashboard.chart.status_donut.view' as any, 'all' as any)"
        cols="12"
        md="5"
      >
        <VCard height="100%">
          <VCardItem>
            <VCardTitle>Order Status</VCardTitle>
            <VCardSubtitle>Distribution by current status</VCardSubtitle>
          </VCardItem>
          <VCardText>
            <VueApexCharts
              type="donut"
              height="280"
              :options="donutConfig"
              :series="donutSeries"
            />
          </VCardText>
        </VCard>
      </VCol>

      <!-- ─── Charts Row 2 ─────────────────────────────────── -->
  

      <!-- Collection & Settlement Radial -->
      <VCol
        v-if="can('order.dashboard.chart.collection_rates.view' as any, 'all' as any)"
        cols="12"
        md="6"
      >
        <VCard height="100%">
          <VCardItem>
            <VCardTitle>Collection &amp; Settlement Rates</VCardTitle>
            <VCardSubtitle>Percentage of completed operations</VCardSubtitle>
          </VCardItem>
          <VCardText>
            <VueApexCharts
              type="radialBar"
              height="280"
              :options="radialConfig"
              :series="radialSeries"
            />
          </VCardText>
        </VCard>
      </VCol>

      <!-- ─── Charts Row 3 ─────────────────────────────────── -->
      <!-- Top 7 Governorates by Shipping Orders -->
      <VCol
        v-if="can('order.dashboard.chart.top_governorates.view' as any, 'all' as any)"
        cols="12"
        md="6"
      >
        <VCard height="100%">
          <VCardItem>
            <VCardTitle>Top 7 Governorates</VCardTitle>
            <VCardSubtitle>Highest shipping activity by order count</VCardSubtitle>
          </VCardItem>
          <VCardText>
            <VueApexCharts
              type="bar"
              height="310"
              :options="governoratesConfig"
              :series="governoratesSeries"
            />
          </VCardText>
        </VCard>
      </VCol>

      <VCol
      v-if="can('order.dashboard.chart.count_breakdown.view' as any, 'all' as any)"
        cols="12"
        md="6"
      >
        <CardWidgetsEarningReportsYearlyOverview
          title="Operations Insights"
          subtitle="Live snapshot based on your dashboard data"
          :reports="yearlyOverviewReports"
        />
      </VCol>

      <!-- ─── Order Counters ───────────────────────────────── -->
      <VCol
        v-if="countCards.length"
        cols="12"
      >
        <h5 class="text-h5 mb-4">
          Order Counters
        </h5>
        <VRow>
          <VCol
            v-for="card in countCards"
            :key="card.key"
            cols="12"
            sm="6"
            md="4"
            lg="3"
          >
            <VCard
              class="dash-stat-card cursor-pointer"
              :style="`--stat-color: var(--v-theme-${card.color})`"
              @click="goToRoute(card.key)"
            >
              <VCardText>
                <div class="d-flex align-center gap-x-3 mb-2">
                  <VAvatar
                    variant="tonal"
                    :color="card.color"
                    rounded
                    size="42"
                  >
                    <VIcon :icon="card.icon" size="24" />
                  </VAvatar>
                  <h5 class="text-h5">
                    {{ card.stat }}
                  </h5>
                </div>
                <div class="text-body-2 text-medium-emphasis">
                  {{ card.title }}
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCol>

      <!-- ─── Financial Cards ──────────────────────────────── -->
      <VCol
        v-if="moneyCards.length"
        cols="12"
      >
        <h5 class="text-h5 mb-4">
          Financial Breakdown
        </h5>
        <VRow>
          <VCol
            v-for="card in moneyCards"
            :key="card.key"
            cols="12"
            sm="6"
            md="4"
            lg="3"
          >
            <VCard
              class="dash-stat-card dash-stat-card--money cursor-pointer"
              :style="`--stat-color: var(--v-theme-${card.color})`"
              @click="goToRoute(card.key)"
            >
              <VCardText>
                <div class="d-flex align-center gap-x-3 mb-2">
                  <VAvatar
                    variant="tonal"
                    :color="card.color"
                    rounded
                    size="42"
                  >
                    <VIcon :icon="card.icon" size="24" />
                  </VAvatar>
                  <div>
                    <div class="text-caption text-disabled d-flex align-center gap-1">
                      <VIcon icon="tabler-currency-pound" size="12" />
                      EGP
                    </div>
                    <h5 class="text-h5">
                      {{ card.stat }}
                    </h5>
                  </div>
                </div>
                <div class="text-body-2 text-medium-emphasis">
                  {{ card.title }}
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCol>
    </template>

    <!-- ─── Empty State ──────────────────────────────────────── -->
    <VCol
      v-if="!isFetching && !error && !cards.length"
      cols="12"
    >
      <VAlert
        type="info"
        variant="tonal"
      >
        No dashboard cards are visible for this account. Card visibility is controlled by permissions.
      </VAlert>
    </VCol>
  </VRow>
</template>

<style lang="scss" scoped>
.dash-stat-card {
  border-block-end: 2px solid rgba(var(--stat-color), 0.38);
  transition: all 0.1s ease-out;

  &:hover {
    border-block-end: 3px solid rgb(var(--stat-color));
    box-shadow: 0 8px 24px -4px rgba(var(--v-shadow-key-umbra-color), 0.2);
    margin-block-end: -1px;
  }
}

.skin--bordered {
  .dash-stat-card {
    &:hover {
      border-block-end: 3px solid rgb(var(--stat-color));
      margin-block-end: -2px;
    }
  }
}
</style>
