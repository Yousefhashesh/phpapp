<script setup lang="ts">
import { useApi } from '@/composables/useApi';
import { useFlashHighlight } from '@/composables/useFlashHighlight'
import { useNotificationStore } from '@/stores/useNotificationStore';
import { createUrl } from '@core/composable/createUrl';
import { AnyCaaRecord } from 'node:dns';
import { useI18n } from 'vue-i18n';

//    Props
const props = defineProps<{
  shipperId?: number | string
  clientId?: number | string
  status?: string
  statusFilter?: string[]
  fixedFilters?: Record<string, any>
  title?: string
  trashed?: 'with' | 'only'
}>()

const { t } = useI18n()
const { flash, isFlashing } = useFlashHighlight()

const importErrors = ref<string[]>([])
const isImportErrorsModalVisible = ref(false)


const isAddEditOrderModalVisible = ref(false)
const isStatusModalVisible = ref(false)
const isShipperModalVisible = ref(false)

const editingOrderId = ref<number | null>(null)
const selectedOrderForStatus = ref<any>(null)
const selectedOrderForShipper = ref<any>(null)
const selectedOrderForDetails = ref<any>(null)
const isDetailsModalVisible = ref(false)
const pageMetadata = ref<any>({})

//    Load Items Per Page from localStorage
const ITEMS_PER_PAGE_STORAGE_KEY = 'orders-items-per-page'
const loadItemsPerPageFromStorage = () => {
  const stored = localStorage.getItem(ITEMS_PER_PAGE_STORAGE_KEY)
  if (stored) {
    const value = Number(stored)
    if ([25, 50, 100].includes(value)) {
      return value
    }
  }
  return 25 // Default value
}

// Search & Filter State
const searchQuery = ref('')
const searchQueryDebounced = ref('')
const selectedStatus = ref<string | null>(props.status || null)
const itemsPerPage = ref(loadItemsPerPageFromStorage())
const page = ref(1)

// Detailed Filters
const filters = ref({
  code: '',
  shipper_date: '',
  receiver_name: '',
  address: '',
  order_note: '',
  is_shipper_collected: null as number | null,
  is_shipper_returned: null as number | null,
  has_return: null as number | null,
  is_client_settled: null as number | null,
  is_client_returned: null as number | null,
})

const booleanOptions = [
  { title: '-', value: null },
  { title: 'Y', value: 1 },
  { title: 'N', value: 0 },
]

// Debounce search
let searchTimer: ReturnType<typeof setTimeout>
watch(searchQuery, val => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    searchQueryDebounced.value = val
    page.value = 1
  }, 650)
})

const openStatusModal = (order: any) => {
  selectedOrderForStatus.value = order
  isStatusModalVisible.value = true
}

const openShipperModal = (order: any) => {
  selectedOrderForShipper.value = order
  isShipperModalVisible.value = true
}

const bulkPrintLabels = () => {
  if (!selectedOrders.value.length) return
  const ids = selectedOrders.value.map(item => {
    if (typeof item === 'object' && item !== null) {
      return item.id
    }
    return item
  }).join(',')
  window.open(`/apps/orders/bulk-print?ids=${ids}`, '_blank')
}

const bulkPrintSmallLabels = () => {
  if (!selectedOrders.value.length) return
  const ids = selectedOrders.value.map(item => {
    if (typeof item === 'object' && item !== null) {
      return item.id
    }
    return item
  }).join(',')
  window.open(`/apps/orders/bulk-delivery-labels?ids=${ids}`, '_blank')
}

//    Headers
const headers = [
  { title: t('CODE'), key: 'code', width: '160px' },
  { title: t('DATE ENTRY'), key: 'created_at', width: '100px' },
  { title: t('SHIPPER DATE'), key: 'shipper_date', width: '100px' },
  { title: t('RECEIVER'), key: 'receiver_name', width: '200px' },
  { title: t('AREA'), key: 'area', width: '220px' },
  { title: t('TOTAL'), key: 'total_amount', width: '80px' },
  { title: t('SHIPPING'), key: 'shipping_fee', width: '80px' },
  { title: t('COMMISSION'), key: 'commission_amount', width: '80px' },
  { title: t('NET'), key: 'company_amount', width: '80px' },
  { title: t('COD'), key: 'cod_amount', width: '80px' },
  { title: t('STATUS'), key: 'status', width: '140px' },
  { title: t('STATUS NOTE'), key: 'latest_status_note', width: '250px' },
  { title: t('ORDER NOTE'), key: 'order_note', width: '200px' },
  { title: t('ت. كابتن'), key: 'shipper_collection', sortable: false, width: '60px' },
  { title: t('م. كابتن'), key: 'shipper_return', sortable: false, width: '60px' },
  { title: t('مرتجع'), key: 'has_return', sortable: false, width: '60px' },
  { title: t('ت. عميل'), key: 'client_settlement', sortable: false, width: '60px' },
  { title: t('م. عميل'), key: 'client_return', sortable: false, width: '60px' },
  { title: t('SHIPPER'), key: 'shipper', width: '150px' },
  { title: t('CLIENT'), key: 'client', width: '150px' },
  { title: t('ACTIONS'), key: 'actions', sortable: false, width: '80px' },
]

const { can } = useAbility()

const STORAGE_KEY = 'orders-visible-columns'

// Define permission mapping for columns
const columnPermissions: Record<string, string> = {
  code: 'order.column.code.view',
  shipper_date: 'order.column.shipper_date.view',
  external_code: 'order.column.external_code.view',
  created_at: 'order.column.created_at.view',
  receiver_name: 'order.column.receiver_name.view',
  area: 'order.column.address.view',
  total_amount: 'order.column.total_amount.view',
  shipping_fee: 'order.column.shipping_fee.view',
  commission_amount: 'order.column.commission_amount.view',
  company_amount: 'order.column.company_amount.view',
  cod_amount: 'order.column.cod_amount.view',
  status: 'order.column.status.view',
  order_note: 'order.column.order_note.view',
  latest_status_note: 'order.column.latest_status_note.view',
  shipper_collection: 'order.column.is_shipper_collected.view',
  shipper_return: 'order.column.is_shipper_returned.view',
  has_return: 'order.column.has_return.view',
  client_settlement: 'order.column.is_client_settled.view',
  client_return: 'order.column.is_client_returned.view',
  shipper: 'order.column.shipper_user_id.view',
  client: 'order.column.client_user_id.view',
}

const loadVisibleHeaderKeys = (): string[] => {
  const defaults = headers.map(h => h.key)
  try {
    const stored = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null')
    if (!Array.isArray(stored)) return defaults
    const keys = stored.filter((k: string) => defaults.includes(k))
    if (!keys.includes('actions')) keys.push('actions')
    return keys.length ? keys : defaults
  } catch {
    return defaults
  }
}

const visibleHeaderKeys = ref(loadVisibleHeaderKeys())

watch(visibleHeaderKeys, keys => {
  const normalized = [...new Set([...keys, 'actions'])]
  localStorage.setItem(STORAGE_KEY, JSON.stringify(normalized))
}, { deep: true })

const activeHeaders = computed(() => {
  return headers.filter(h => {
    if (h.key === 'actions') return true

    if (!visibleHeaderKeys.value.includes(h.key)) return false

    const perm = columnPermissions[h.key]
    if (perm && !can(perm as any, 'all' as any)) return false

    return true
  })
})

const filteredHeadersForMenu = computed(() => {
  return headers.filter(h => {
    if (h.key === 'actions') return false
    const perm = columnPermissions[h.key]
    return !perm || can(perm as any, 'all' as any)
  })
})

const resolveRowItem = (item: any) => item?.raw ?? item

//    Filters State (Others)
const selectedGovernorate = ref<number | null>(null)
const selectedShipper = ref<number | null>(props.shipperId ? Number(props.shipperId) : null)
const selectedClient = ref<number | null>(props.clientId ? Number(props.clientId) : null)

//    State Refs
const selectedOrders = ref<any[]>([])
const isBulkStatusModalVisible = ref(false)
const isBulkShipperModalVisible = ref(false)

const governorates = ref<any[]>([])
const shippers = ref<any[]>([])
const clients = ref<any[]>([])
const orders = ref<any[]>([])
const totalOrders = ref(0)
const isLoading = ref(false)

const notificationStatus = useNotificationStore()
const notify = (msg: string, color: string = 'success') => {
  notificationStatus.notify(msg, color)
}

const totals = ref({
  total_amount: 0,
  total_cod: 0,
  total_shipping: 0,
  total_commission: 0,
  total_net: 0,
})

const searchShippers = async (val: string = '') => {
  try {
    const { data: res } = await useApi<any>(createUrl('/shippers', { query: { q: val, governorate_id: selectedGovernorate.value, per_page: 20 } })).get().json()
    const data = (res.value?.data || res.value || [])
    shippers.value = data.map((s: any) => ({
      id: s.user_id,
      user_id: s.user_id,
      name: s.user?.name || 'Unknown',
      commission_rate: s.commission_rate
    }))
  } catch (e) { console.error(e) }
}

watch(selectedGovernorate, () => {
  selectedShipper.value = null
  searchShippers()
})

const openBulkStatusModal = () => { isBulkStatusModalVisible.value = true }
const openBulkShipperModal = () => { isBulkShipperModalVisible.value = true }

const exportOrders = () => {
  const params = new URLSearchParams()
  
  if (selectedOrders.value.length > 0) {
    const ids = selectedOrders.value.map(o => o.id || o).join(',')
    params.append('ids', ids)
  } else {
    // Add current filters
    if (searchQuery.value) params.append('q', searchQuery.value)
    if (selectedStatus.value) params.append('status', selectedStatus.value)
    if (selectedGovernorate.value) params.append('governorate_id', String(selectedGovernorate.value))
    if (selectedShipper.value) params.append('shipper_user_id', String(selectedShipper.value))
    if (selectedClient.value) params.append('client_user_id', String(selectedClient.value))
    
    // Detailed filters
    Object.entries(filters.value).forEach(([key, val]) => {
      if (val !== null && val !== '') params.append(key, String(val))
    })
  }

  const token = useCookie('accessToken').value || ''
  window.open(`/api/orders/export?${params.toString()}&token=${token}`, '_blank')
}

const inputRef = ref<HTMLInputElement | null>(null)

const downloadTemplate = () => {
  const token = useCookie('accessToken').value || ''
  window.open(`/api/orders/import-template?token=${token}`, '_blank')
}

// const handleImport = async (event: Event) => {
//   if (!isWithinWorkingHours('orders')) {
//     const meta = pageMetadata.value?.working_hours
//     const start = meta?.working_hours_orders_start || '08:00'
//     const end = meta?.working_hours_orders_end || '22:00'
//     alert(`لا يمكن الاستيراد الآن. مواعيد العمل الرسمية: من ${start} حتى ${end}`)
//     return
//   }
//   const target = event.target as HTMLInputElement
//   if (!target.files?.length) return

//   const file = target.files[0]
//   const formData = new FormData()
//   formData.append('file', file)

//   try {
//     const { data, error } = await useApi('/orders/import').post(formData).json()
//     if (!error.value) {
//       notify(`Import Successful! ${data.value.success_count} orders created.`, 'success')
//       if (data.value.errors?.length) {
//          console.warn('Import Errors:', data.value.errors)
//          notify('Some rows had errors. Check console for details.', 'warning')
//       }
//       fetchOrders()
//     } else {
//       notify('Import failed: ' + (error.value?.message || 'Check file format'), 'error')
//     }
//   } catch (e) {
//     console.error(e)
//     notify('Network error during import', 'error')
//   }
//   // Clear input
//   target.value = ''
// }

const handleImport = async (event: Event) => {
  if (!isWithinWorkingHours('orders')) {
    const meta = pageMetadata.value?.working_hours
    const start = meta?.working_hours_orders_start || '08:00'
    const end = meta?.working_hours_orders_end || '22:00'
    alert(`لا يمكن الاستيراد الآن. مواعيد العمل الرسمية: من ${start} حتى ${end}`)
    return
  }
  const target = event.target as HTMLInputElement
  if (!target.files?.length) return

  const file = target.files[0]
  const formData = new FormData()
  formData.append('file', file)

  importErrors.value = []

  try {
    const { data, error } = await useApi('/orders/import').post(formData).json()

    if (!error.value) {
      const successCount = data.value.success_count ?? 0
      const errors = data.value.errors || []

      if (successCount > 0) {
        notify(`تم استيراد ${successCount} أوردر بنجاح.`, 'success')
        fetchOrders()
      }

      if (errors.length) {
        importErrors.value = errors
        isImportErrorsModalVisible.value = true

        if (successCount === 0) {
          notify('لم يتم استيراد أي أوردر، يوجد أخطاء في الملف.', 'error')
        } else {
          notify(`فشلت ${errors.length} صف/صفوف من ضمن الملف.`, 'warning')
        }
      }
    } else {
      notify('فشل الاستيراد: ' + (error.value?.message || 'تحقق من صيغة الملف'), 'error')
    }
  } catch (e) {
    console.error(e)
    notify('حدث خطأ في الشبكة أثناء الاستيراد', 'error')
  }

  target.value = ''
}

const searchClients = async (val: string = '') => {
  try {
    const { data: res } = await useApi<any>(createUrl('/clients', { query: { q: val, per_page: 20 } })).get().json()
    const data = (res.value?.data || res.value || [])
    clients.value = data.map((c: any) => ({
      id: c.user_id,
      name: c.user?.name || 'Unknown',
      plan_id: c.plan_id,
      shipping_content_id: c.shipping_content_id,
      shipping_fee: c.shipping_fee
    }))
  } catch (e) { console.error(e) }
}

const applyOrdersData = (oData: any) => {
  orders.value = oData.data || []
  totalOrders.value = oData.total || 0

  const t = oData.totals
  if (t) {
    totals.value = {
      total_amount: t.total_amount ?? 0,
      total_cod: t.cod_amount ?? 0,
      total_shipping: t.shipping_fee ?? 0,
      total_commission: t.commission_amount ?? 0,
      total_net: t.company_amount ?? 0,
    }
  }
}

const refreshSingleOrder = async (orderId: number) => {
  try {
    const { data } = await useApi<any>(`/orders/${orderId}`).get().json()
    if (!data.value)
      return

    const index = orders.value.findIndex(o => o.id === orderId)
    if (index === -1)
      return

    orders.value[index] = { ...orders.value[index], ...data.value }
    orders.value = [...orders.value]
    flash(`order-${orderId}`)
  } catch (e) {
    console.error(e)
  }
}

const refreshMultipleOrders = async (orderIds: number[]) => {
  const uniqueIds = [...new Set(orderIds.filter(id => Number.isInteger(id)))]
  if (!uniqueIds.length)
    return

  await Promise.all(uniqueIds.map(id => refreshSingleOrder(id)))
}

const onOrderSaved = (orderId?: number | null) => {
  if (orderId)
    refreshSingleOrder(orderId)
  else
    fetchOrders()
}

const onStatusUpdated = (orderId?: number) => {
  if (orderId)
    refreshSingleOrder(orderId)
}

const onShipperUpdated = (orderId?: number) => {
  if (orderId)
    refreshSingleOrder(orderId)
}

const onBulkStatusUpdated = (orderIds?: number[]) => {
  selectedOrders.value = []
  if (orderIds?.length)
    refreshMultipleOrders(orderIds)
  else
    fetchOrders()
}

const onBulkShipperUpdated = (orderIds?: number[]) => {
  selectedOrders.value = []
  if (orderIds?.length)
    refreshMultipleOrders(orderIds)
  else
    fetchOrders()
}

const fetchOrders = async () => {
  isLoading.value = true
  try {
    const { data: oData } = await useApi<any>(createUrl('/orders', {
      query: {
        q: searchQueryDebounced.value,
        status: Array.isArray(todayStatusFilter.value || selectedStatus.value || props.statusFilter)
          ? (todayStatusFilter.value || selectedStatus.value || props.statusFilter).join(',')
          : (todayStatusFilter.value || selectedStatus.value || props.statusFilter),
        approval_status: props.fixedFilters && 'approval_status' in props.fixedFilters 
          ? (Array.isArray(props.fixedFilters.approval_status) ? props.fixedFilters.approval_status.join(',') : props.fixedFilters.approval_status)
          : 'APPROVED',
        governorate_id: selectedGovernorate.value,
        shipper_user_id: selectedShipper.value,
        client_user_id: selectedClient.value,
        per_page: itemsPerPage.value,
        page: page.value,
        trashed: props.trashed,
        ...filters.value,
        ...Object.fromEntries(
          Object.entries(props.fixedFilters || {}).map(([k, v]) => [k, Array.isArray(v) ? v.join(',') : v])
        ),
      },
    })).get().json()

    if (oData.value) {
      applyOrdersData(oData.value)
    }
  } catch (e) { /*  */ }
  isLoading.value = false
}

const initializePage = async () => {
  isLoading.value = true

  try {
    const { data: res, error } = await useApi<any>(createUrl('/orders/init', {
      query: {
        q: searchQueryDebounced.value,
        status: Array.isArray(todayStatusFilter.value || selectedStatus.value || props.statusFilter)
          ? (todayStatusFilter.value || selectedStatus.value || props.statusFilter).join(',')
          : (todayStatusFilter.value || selectedStatus.value || props.statusFilter),
        approval_status: props.fixedFilters && 'approval_status' in props.fixedFilters 
          ? (Array.isArray(props.fixedFilters.approval_status) ? props.fixedFilters.approval_status.join(',') : props.fixedFilters.approval_status)
          : 'APPROVED',
        governorate_id: selectedGovernorate.value,
        shipper_user_id: selectedShipper.value,
        client_user_id: selectedClient.value,
        per_page: itemsPerPage.value,
        page: page.value,
        trashed: props.trashed,
        ...filters.value,
        ...Object.fromEntries(
          Object.entries(props.fixedFilters || {}).map(([k, v]) => [k, Array.isArray(v) ? v.join(',') : v])
        ),
      },
    })).get().json()



    if (res.value) {

      // Handle metadata
      pageMetadata.value = res.value.metadata
      const meta = res.value.metadata
      governorates.value = meta.governorates || []
      shippers.value = meta.shippers || []
      clients.value = meta.clients || []

      // Handle first page orders
      applyOrdersData(res.value.orders)
    }
  } catch (e) {
    // 
    fetchOrders()
  }
  isLoading.value = false
}

onMounted(() => {
  initializePage()
})

// Debounce filter and search updates
let updateTimer: ReturnType<typeof setTimeout>

// 1. Watch for Filter changes: Reset to page 1 and fetch
watch([searchQueryDebounced, selectedStatus, selectedGovernorate, selectedShipper, selectedClient, filters], () => {
  page.value = 1
  clearTimeout(updateTimer)
  updateTimer = setTimeout(() => {
    fetchOrders()
  }, 300)
}, { deep: true })

// 2. Watch for Pagination: Just fetch
watch([page, itemsPerPage], () => {
  clearTimeout(updateTimer)
  updateTimer = setTimeout(() => {
    fetchOrders()
  }, 100)
})

const statusColors: any = {
  OUT_FOR_DELIVERY: 'primary',
  DELIVERED: 'success',
  HOLD: 'warning',
  UNDELIVERED: 'error',
}
const resolveStatusColor = (status: string) => statusColors[status] || 'secondary'

const resetFilters = () => {
  searchQuery.value = ''
  searchQueryDebounced.value = ''
  selectedStatus.value = props.status || null
  selectedGovernorate.value = null
  selectedShipper.value = props.shipperId ? Number(props.shipperId) : null
  selectedClient.value = props.clientId ? Number(props.clientId) : null
  filters.value = {
    code: '',
    shipper_date: '',
    receiver_name: '',
    address: '',
    order_note: '',
    is_shipper_collected: null,
    is_shipper_returned: null,
    has_return: null,
    is_client_settled: null,
    is_client_returned: null,
  }
  page.value = 1
}

const todayStatusFilter = ref<string[] | null>(null)

const applyTodayFilter = () => {
  resetFilters()
  todayStatusFilter.value = ['HOLD', 'OUT_FOR_DELIVERY']
  page.value = 1
  fetchOrders()
}

const editOrder = (id: number) => {
  editingOrderId.value = id
  isAddEditOrderModalVisible.value = true
}

const getStateConfig = (isDone: boolean, isWaiting: boolean, date: string | null) => {
  if (isDone) return { color: 'success', label: date ? new Date(date).toLocaleDateString('en-GB') : '✓' }
  if (isWaiting) return { color: 'warning', label: '✕' }
  return { color: 'error', label: '✕' }
}
const printLabel = (id: number) => {
  window.open(`/apps/orders/shipping-label/${id}`, '_blank')
}

const printSmallLabel = (id: number) => {
  window.open(`/apps/orders/small-shipping-label/${id}`, '_blank')
}

const deleteOrder = async (id: number) => {
  if (!confirm('Are you sure you want to delete this order?')) return

  const { error } = await useApi(`/orders/${id}`).delete()
  if (!error.value) {
    fetchOrders()
  }
}

const restoreOrder = async (id: number) => {
  if (!confirm('Are you sure you want to restore this order?')) return

  const { error } = await useApi(`/orders/${id}/restore`).patch().json()
  if (!error.value) {
    fetchOrders()
  }
}

const forceDeleteOrder = async (id: number) => {
  if (!confirm('Are you sure you want to PERMANENTLY delete this order?')) return

  const { error } = await useApi(`/orders/${id}/force-delete`).delete()
  if (!error.value) {
    fetchOrders()
  }
}

const bulkDeleteOrders = async () => {
  if (!selectedOrders.value.length) return
  if (!confirm(`Are you sure you want to delete ${selectedOrders.value.length} selected orders?`)) return

  const ids = selectedOrders.value.map(item => typeof item === 'object' ? item.id : item)
  
  const { error } = await useApi('/orders/bulk-delete').delete({
    order_ids: ids
  })

  if (!error.value) {
    selectedOrders.value = []
    fetchOrders()
    notify('Bulk delete completed successfully')
  }
}

const bulkRestoreOrders = async () => {
  if (!selectedOrders.value.length) return
  if (!confirm(`Are you sure you want to restore ${selectedOrders.value.length} selected orders?`)) return

  const ids = selectedOrders.value.map(item => typeof item === 'object' ? item.id : item)
  
  const { error } = await useApi('/orders/bulk-restore').patch({
    order_ids: ids
  })

  if (!error.value) {
    selectedOrders.value = []
    fetchOrders()
    notify('Bulk restore completed successfully')
  }
}

const bulkForceDeleteOrders = async () => {
  if (!selectedOrders.value.length) return
  if (!confirm(`Are you sure you want to PERMANENTLY delete ${selectedOrders.value.length} selected orders?`)) return

  const ids = selectedOrders.value.map(item => typeof item === 'object' ? item.id : item)
  
  const { error } = await useApi('/orders/bulk-force-delete').delete({
    order_ids: ids
  })

  if (!error.value) {
    selectedOrders.value = []
    fetchOrders()
    notify('Bulk permanent delete completed successfully')
  }
}

const openDetails = (order: any) => {
  selectedOrderForDetails.value = order
  isDetailsModalVisible.value = true
}

const getLogIcon = (log: any) => {
  if (log.action === 'created') return 'tabler-circle-plus'
  if (log.new_values?.status) return 'tabler-circle-check'
  if (log.new_values?.is_shipper_collected) return 'tabler-cash'
  if (log.new_values?.is_shipper_returned) return 'tabler-arrow-back-up'
  if (log.new_values?.is_client_settled) return 'tabler-mood-dollar'
  if (log.new_values?.is_client_returned) return 'tabler-package-export'
  if (log.new_values?.shipper_user_id) return 'tabler-truck'
  return 'tabler-circle-check'
}

const getLogColor = (log: any) => {
  if (log.action === 'created') return 'success'
  if (log.new_values?.status) return resolveStatusColor(log.new_values.status)
  if (log.new_values?.is_shipper_collected) return 'success'
  if (log.new_values?.is_shipper_returned) return 'warning'
  if (log.new_values?.is_client_settled) return 'info'
  if (log.new_values?.is_client_returned) return 'error'
  if (log.new_values?.shipper_user_id) return 'info'
  return 'primary'
}

const getLogActionLabel = (log: any) => {
  if (log.action === 'created') return 'إنشاء الطلب'
  if (log.new_values?.status) return 'تغيير الحالة'
  if (log.new_values?.is_shipper_collected) return 'تحصيل المندوب'
  if (log.new_values?.is_shipper_returned) return 'مرتجع المندوب'
  if (log.new_values?.is_client_settled) return 'تسوية العميل'
  if (log.new_values?.is_client_returned) return 'مرتجع العميل'
  if (log.new_values?.shipper_user_id) return 'تعيين مندوب'
  return 'تحديث بيانات'
}

const getLogMessage = (log: any) => {
  if (log.action === 'created') return 'تم تسجيل الأوردر بنجاح'
  if (log.new_values?.status) return `تم تغيير حالة الأوردر إلى ${log.new_values.status.replace(/_/g, ' ')}`
  if (log.new_values?.is_shipper_collected) return 'تم تأكيد تحصيل المبلغ من المندوب'
  if (log.new_values?.is_shipper_returned) return 'تم تنفيذ مرتجع المندوب للمخزن'
  if (log.new_values?.is_client_settled) return 'تمت تسوية المستحقات المالية مع العميل'
  if (log.new_values?.is_client_returned) return 'تم تسليم المرتجع للعميل رسمياً'
  if (log.new_values?.shipper_user_id) return 'تم تعيين مندوب جديد للتوصيل'
  if (log.message) return log.message
  return 'تم تحديث بيانات الأوردر'
}

// Working Hours Logic
const isWithinWorkingHours = (type: 'orders' | 'pickups' | 'material_requests') => {
  const meta = pageMetadata.value?.working_hours
  if (!meta) return true
  
  if (can('setting.bypass-working-hours' as any, 'all' as any)) return true

  const start = meta[`working_hours_${type}_start`] || '08:00'
  const end = meta[`working_hours_${type}_end`] || '22:00'
  
  const now = new Date()
  const currentStr = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`
  
  if (start < end) {
    return currentStr >= start && currentStr <= end
  } else {
    return currentStr >= start || currentStr <= end
  }
}

const handleNewOrder = () => {
  if (!isWithinWorkingHours('orders')) {
    const meta = pageMetadata.value?.working_hours
    const start = meta?.working_hours_orders_start || '08:00'
    const end = meta?.working_hours_orders_end || '22:00'
    alert(`لا يمكن إنشاء الأوردر الآن. مواعيد العمل الرسمية: من ${start} حتى ${end}`)
    return
  }
  editingOrderId.value = null
  isAddEditOrderModalVisible.value = true
}

const copyOrderToClipboard = (item: any) => {
  const text = `
كود: #${item.code}
الكود الخارجي: ${item.external_code || '-'}
المرسل إليه: ${item.receiver_name}
موبايل: ${item.phone} ${item.phone_2 ? '/ ' + item.phone_2 : ''}
العنوان: ${item.governorate?.name} - ${item.city?.name} - ${item.address}
الإجمالي: ${item.total_amount} ج.م
  `.trim()

  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(() => {
      alert('تم نسخ بيانات الأوردر')
    })
  } else {
    const textArea = document.createElement("textarea")
    textArea.value = text
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)
    alert('تم نسخ بيانات الأوردر')
  }
}

const sendToWhatsApp = (item: any) => {
  const phone = item.phone.replace(/\D/g, '')
  const text = `
كود: #${item.code}
الكود الخارجي: ${item.external_code || '-'}
المرسل إليه: ${item.receiver_name}
موبايل: ${item.phone} ${item.phone_2 ? '/ ' + item.phone_2 : ''}
العنوان: ${item.governorate?.name} - ${item.city?.name} - ${item.address}
الإجمالي: ${item.total_amount} ج.م
  `.trim()
  
  const waPhone = phone.startsWith('0') ? '2' + phone : phone
  const url = `https://wa.me/${waPhone}?text=${encodeURIComponent(text)}`
  window.open(url, '_blank')
}

// ===== Financial Action Buttons =====

const processingFinancialAction = ref(false)

// Shipper Collection: Create or Cancel
const toggleShipperCollection = async (item: any) => {
  processingFinancialAction.value = true
  try {
    if (item.is_shipper_collected) {
      // Cancel: find active collection containing this order and cancel it
      // We need to set is_shipper_collected = false by removing from collection
      // But the correct approach is: the user should go to the collection page.
      // For now, toggle via order flag
      notify('لإلغاء التحصيل، يرجى الذهاب لصفحة تحصيلات المناديب وإلغاء التحصيل المرتبط.', 'warning')
    } else {
      // Create new collection with this single order
      if (!item.shipper_user_id) {
        notify('هذا الأوردر ليس مُسند لمندوب', 'error')
        return
      }
      const { data, error } = await useApi('/shipper-collections')
        .post({
          shipper_user_id: item.shipper_user_id,
          order_ids: [item.id],
        })
        .json()

      if (!error.value) {
        notify('تم إنشاء تحصيل الكابتن بنجاح', 'success')
        refreshSingleOrder(item.id)
      } else {
        const msg = (error.value as any)?.data?.message || (error.value as any)?.message || 'حدث خطأ'
        notify(msg, 'error')
      }
    }
  } catch (e) {
    notify('حدث خطأ أثناء العملية', 'error')
  } finally {
    processingFinancialAction.value = false
  }
}

// Client Settlement: Create or Cancel
const toggleClientSettlement = async (item: any) => {
  processingFinancialAction.value = true
  try {
    if (item.is_client_settled) {
      notify('لإلغاء التسوية، يرجى الذهاب لصفحة تسويات العملاء وإلغاء التسوية المرتبطة.', 'warning')
    } else {
      if (!item.client_user_id) {
        notify('هذا الأوردر ليس مُسند لعميل', 'error')
        return
      }
      const { data, error } = await useApi('/client-settlements')
        .post({
          client_user_id: item.client_user_id,
          settlement_date: new Date().toISOString().substr(0, 10),
          total_amount: Number(item.total_amount) || 0,
          number_of_orders: 1,
          order_ids: [item.id],
        })
        .json()

      if (!error.value) {
        notify('تم إنشاء تسوية العميل بنجاح', 'success')
        refreshSingleOrder(item.id)
      } else {
        const msg = (error.value as any)?.data?.message || (error.value as any)?.message || 'حدث خطأ'
        notify(msg, 'error')
      }
    }
  } catch (e) {
    notify('حدث خطأ أثناء العملية', 'error')
  } finally {
    processingFinancialAction.value = false
  }
}

// Shipper Return
const createShipperReturn = async (item: any) => {
  processingFinancialAction.value = true
  try {
    if (!item.shipper_user_id) {
      notify('هذا الأوردر ليس مُسند لمندوب', 'error')
      return
    }
    const { data, error } = await useApi('/shipper-returns')
      .post({
        shipper_user_id: item.shipper_user_id,
        return_date: new Date().toISOString().substring(0, 10),
        number_of_orders: 1,
        order_ids: [item.id],
      })
      .json()

    if (!error.value) {
      notify('تم إنشاء مرتجع الكابتن بنجاح', 'success')
      refreshSingleOrder(item.id)
    } else {
      const msg = (error.value as any)?.data?.message || (error.value as any)?.message || 'حدث خطأ'
      notify(msg, 'error')
    }
  } catch (e) {
    notify('حدث خطأ أثناء العملية', 'error')
  } finally {
    processingFinancialAction.value = false
  }
}

// Client Return
const createClientReturn = async (item: any) => {
  processingFinancialAction.value = true
  try {
    if (!item.client_user_id) {
      notify('هذا الأوردر ليس مُسند لعميل', 'error')
      return
    }
    const { data, error } = await useApi('/client-returns')
      .post({
        client_user_id: item.client_user_id,
        return_date: new Date().toISOString().substring(0, 10),
        number_of_orders: 1,
        order_ids: [item.id],
      })
      .json()

    if (!error.value) {
      notify('تم إنشاء مرتجع العميل بنجاح', 'success')
      refreshSingleOrder(item.id)
    } else {
      const msg = (error.value as any)?.data?.message || (error.value as any)?.message || 'حدث خطأ'
      notify(msg, 'error')
    }
  } catch (e) {
    notify('حدث خطأ أثناء العملية', 'error')
  } finally {
    processingFinancialAction.value = false
  }
}

//    Initial fetch
searchShippers()
searchClients()
</script>

<template>
  <section>
    <!--    Stats Cards -->
    <VRow class="mb-2">
      <VCol cols="6" md="2">
        <VCard elevation="2" class="stats-card"><VCardText class="d-flex align-center gap-3 pa-3">
            <VAvatar variant="tonal" color="primary" icon="tabler-package" size="38" />
            <div><div class="text-h6 font-weight-bold">{{ totalOrders }}</div><div class="text-xs text-disabled">Orders</div></div>
        </VCardText></VCard>
      </VCol>
      <VCol cols="6" md="2">
        <VCard elevation="2" class="stats-card"><VCardText class="d-flex align-center gap-3 pa-3">
            <VAvatar variant="tonal" color="info" icon="tabler-currency-dollar" size="38" />
            <div><div class="text-h6 font-weight-bold text-truncate" style="max-inline-size: 80px;">{{ totals.total_amount.toFixed(0) }}</div><div class="text-xs text-disabled">Amount</div></div>
        </VCardText></VCard>
      </VCol>
      <VCol cols="6" md="2">
        <VCard elevation="2" class="stats-card"><VCardText class="d-flex align-center gap-3 pa-3">
            <VAvatar variant="tonal" color="success" icon="tabler-cash" size="38" />
            <div><div class="text-h6 font-weight-bold">{{ totals.total_cod.toFixed(0) }}</div><div class="text-xs text-disabled">COD</div></div>
        </VCardText></VCard>
      </VCol>
      <VCol cols="6" md="2">
        <VCard elevation="2" class="stats-card"><VCardText class="d-flex align-center gap-3 pa-3">
            <VAvatar variant="tonal" color="secondary" icon="tabler-truck-delivery" size="38" />
            <div><div class="text-h6 font-weight-bold">{{ totals.total_shipping.toFixed(0) }}</div><div class="text-xs text-disabled">Fees</div></div>
        </VCardText></VCard>
      </VCol>
      <VCol cols="6" md="2" v-if="can('shipper.column.commission_rate.view' as any ,'all' as any) ">
        <VCard elevation="2" class="stats-card">
          <VCardText class="d-flex align-center gap-3 pa-3">
            <VAvatar variant="tonal" color="error" icon="tabler-user-share" size="38" />
            <div><div class="text-h6 font-weight-bold">{{ totals.total_commission.toFixed(0) }}</div><div class="text-xs text-disabled">Shipper Fees</div></div>
        </VCardText>
      </VCard>
      </VCol>
      <VCol cols="6" md="2" v-if="can('order.dashboard.card.total_cop.view' as any ,'all' as any) " >
        <VCard elevation="2" class="stats-card"><VCardText class="d-flex align-center gap-3 pa-3">
            <VAvatar variant="tonal" color="warning" icon="tabler-wallet" size="38" />
            <div><div class="text-h6 font-weight-bold">{{ totals.total_net.toFixed(0) }}</div><div class="text-xs text-disabled">COP</div></div>
        </VCardText></VCard>
      </VCol>
    </VRow>

    <VCard elevation="2">
      <VCardTitle class="pt-4 px-6 pb-0">
        <h5 class="text-h5">{{ props.title || t('Orders Management') }}</h5>
      </VCardTitle>

      <!-- 📦 Bulk Actions Row (Header Position) -->
      <VCardText v-show="selectedOrders.length" class="bg-light-primary py-2 border-bottom border-top rounded-0">
        <div class="d-flex align-center gap-4 flex-wrap">
          <div class="text-subtitle-2 text-primary font-weight-bold">
            <VChip color="primary" size="small" class="me-2">{{ selectedOrders.length }}</VChip>
            {{ t('Elements Selected') }}
          </div>
          <VDivider vertical class="mx-2" />
          <template v-if="props.trashed === 'only'">
            <VBtn v-if="can('order.delete' as any, 'all' as any)" size="small" color="primary" variant="elevated" prepend-icon="tabler-refresh" @click="bulkRestoreOrders">{{ t('Bulk Restore') }}</VBtn>
            <VBtn v-if="can('order.delete' as any, 'all' as any)" size="small" color="error" variant="elevated" prepend-icon="tabler-trash-x" @click="bulkForceDeleteOrders">{{ t('Bulk Permanent Delete') }}</VBtn>
          </template>
          <template v-else>
            <VBtn v-if="can('order.change-shipper' as any, 'all' as any)" size="small" color="primary" variant="elevated" prepend-icon="tabler-truck" @click="openBulkShipperModal">{{ t('Change Shipper') }}</VBtn>
            <VBtn v-if="can('order.view' as any, 'all' as any)" size="small" color="info" variant="elevated" prepend-icon="tabler-file-invoice" @click="bulkPrintLabels">{{ t('Print Labels') }}</VBtn>
            <VBtn v-if="can('order.view' as any, 'all' as any)" size="small" color="secondary" variant="elevated" prepend-icon="tabler-tags" @click="bulkPrintSmallLabels">ليبل صغير</VBtn>
            <VBtn v-if="can('order.export' as any, 'all' as any)" size="small" color="secondary" variant="elevated" prepend-icon="tabler-file-spreadsheet" @click="exportOrders">{{ t('Export Excel') }}</VBtn>
            <VBtn v-if="can('order.change-status' as any, 'all' as any)" size="small" color="success" variant="elevated" prepend-icon="tabler-settings" @click="openBulkStatusModal">{{ t('Change Status') }}</VBtn>
            <VBtn v-if="can('order.delete' as any, 'all' as any)" size="small" color="error" variant="elevated" prepend-icon="tabler-trash" @click="bulkDeleteOrders">{{ t('Bulk Delete') }}</VBtn>
          </template>
          <VSpacer />
          <VBtn icon size="x-small" variant="text" color="secondary" @click="selectedOrders = []"><VIcon icon="tabler-x" /></VBtn>
        </div>
      </VCardText>

      <VCardText class="pb-2">
        <VRow align="center">
          <VCol cols="12" md="2">
            <AppTextField v-model="searchQuery" :placeholder="t('Quick Search...')" prepend-inner-icon="solar:magnifer-bold" density="compact" hide-details />
          </VCol>
          <VCol cols="12" md="2">
            <AppSelect
              v-model="selectedStatus"
              :placeholder="t('Status')"
              :items="['OUT_FOR_DELIVERY', 'DELIVERED', 'HOLD', 'UNDELIVERED']"
              clearable
              density="compact"
              hide-details
            />
          </VCol>
          <VCol cols="auto">
            <VBtn variant="tonal" color="secondary" size="small" @click="resetFilters"><VIcon start icon="tabler-refresh" />{{ t('Reset') }}</VBtn>
          </VCol>
          <VCol cols="auto">
            <VBtn variant="tonal" color="info" size="small" prepend-icon="tabler-calendar-event" @click="applyTodayFilter">{{ t('Today\'s Orders') }}</VBtn>
          </VCol>
          <VCol cols="auto" class="ms-auto text-end d-flex gap-2">
            <VMenu>
              <template #activator="{ props }">
                <VBtn color="secondary" size="small" variant="tonal" prepend-icon="tabler-file-import" v-bind="props">{{ t('Import') }}</VBtn>
              </template>
              <VList box-shadow="2">
                <VListItem @click="downloadTemplate">
                  <template #prepend><VIcon icon="tabler-download" size="20" class="me-2"/></template>
                  <VListItemTitle>{{ t('Download Template') }}</VListItemTitle>
                </VListItem>
                <VListItem @click="inputRef?.click()">
                  <template #prepend><VIcon icon="tabler-upload" size="20" class="me-2"/></template>
                  <VListItemTitle>{{ t('Upload Orders') }}</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
            <input ref="inputRef" type="file" class="d-none" accept=".xlsx,.csv" @change="handleImport">

            <VBtn v-if="can('order.export', 'all')" color="secondary" size="small" variant="tonal" prepend-icon="tabler-file-spreadsheet" @click="exportOrders">{{ t('Export All') }}</VBtn>
            <VBtn v-if="can('order.create', 'all')" color="primary" size="small" prepend-icon="tabler-plus" @click="handleNewOrder">{{ t('New Order') }}</VBtn>

            <!--    Column Visibility Toggle -->
            <VMenu :close-on-content-click="false">
              <template #activator="{ props }">
                <VBtn icon size="small" variant="tonal" color="secondary" v-bind="props" class="ms-2">
                  <VIcon icon="tabler-layout-columns" />
                </VBtn>
              </template>
              <VList class="pa-2" style="max-block-size: 400px; overflow-y: auto;">
                <VListItem v-for="h in filteredHeadersForMenu" :key="h.key" density="compact">
                  <VCheckbox v-model="visibleHeaderKeys" :value="h.key" :label="h.title" hide-details density="compact" />
                </VListItem>
              </VList>
            </VMenu>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VDataTableServer
        v-model="selectedOrders"
        v-model:page="page"
        :loading="isLoading"
        :items="orders"
        :items-length="totalOrders"
        :headers="activeHeaders"
        item-value="id"
        return-object
        show-select
        class="text-no-wrap filter-table"
        :items-per-page="itemsPerPage"
        hide-default-footer
        :row-props="({ item }: { item: any }) => ({
          class: isFlashing(`order-${item.id}`) ? 'row-flash' : '',
        })"
        loading-text="تحميل البيانات..."
      >
        <!--    Header Filter Slots -->
        <template #header.code="{ column }">
          <div class="header-filter">
            <span class="header-title">{{ column.title }}</span>
            <VTextField v-model="filters.code" density="compact" hide-details variant="outlined" placeholder="بحث..." class="filter-input-outlined" />
          </div>
        </template>
        <template #header.shipper_date="{ column }">
          <div class="header-filter">
            <span class="header-title">{{ column.title }}</span>
            <VTextField v-model="filters.shipper_date" density="compact" hide-details variant="outlined" placeholder="التاريخ" class="filter-input-outlined" />
          </div>
        </template>
        <template #header.receiver_name="{ column }">
          <div class="header-filter">
            <span class="header-title">{{ column.title }}</span>
            <VTextField v-model="filters.receiver_name" density="compact" hide-details variant="outlined" placeholder="المرسل إليه" class="filter-input-outlined" />
          </div>
        </template>
        <template #header.area="{ column }">
          <div class="header-filter">
            <span class="header-title">{{ column.title }}</span>
            <VTextField v-model="filters.address" density="compact" hide-details variant="outlined" placeholder="المنطقة" class="filter-input-outlined" />
          </div>
        </template>
        <template #header.order_note="{ column }">
          <div class="header-filter">
            <span class="header-title">{{ column.title }}</span>
            <VTextField v-model="filters.order_note" density="compact" hide-details variant="outlined" placeholder="ملاحظات" class="filter-input-outlined" />
          </div>
        </template>

        <!-- Small Boolean Header Filters -->
        <template #header.shipper_collection="{ column }">
          <div class="header-filter"><span class="header-title">تحصيل</span>
            <VSelect v-model="filters.is_shipper_collected" :items="booleanOptions" density="compact" hide-details variant="outlined" class="filter-select-outlined" />
          </div>
        </template>
        <template #header.shipper_return="{ column }">
          <div class="header-filter"><span class="header-title">مرتجع</span>
            <VSelect v-model="filters.is_shipper_returned" :items="booleanOptions" density="compact" hide-details variant="outlined" class="filter-select-outlined" />
          </div>
        </template>
        <template #header.has_return="{ column }">
          <div class="header-filter"><span class="header-title">فيه م</span>
            <VSelect v-model="filters.has_return" :items="booleanOptions" density="compact" hide-details variant="outlined" class="filter-select-outlined" />
          </div>
        </template>
        <template #header.client_settlement="{ column }">
          <div class="header-filter"><span class="header-title">تسوية</span>
            <VSelect v-model="filters.is_client_settled" :items="booleanOptions" density="compact" hide-details variant="outlined" class="filter-select-outlined" />
          </div>
        </template>
        <template #header.client_return="{ column }">
          <div class="header-filter"><span class="header-title">م. عميل</span>
            <VSelect v-model="filters.is_client_returned" :items="booleanOptions" density="compact" hide-details variant="outlined" class="filter-select-outlined" />
          </div>
        </template>

        <template #header.shipper="{ column }">
          <div class="header-filter"><span class="header-title">{{ column.title }}</span>
            <VTextField v-model="selectedShipper" :items="shippers" item-title="name" item-value="id" clearable density="compact" hide-details variant="outlined" class="filter-select-outlined" placeholder="المندوب" @update:search="searchShippers" />
          </div>
        </template>
        <template #header.client="{ column }">
          <div class="header-filter"><span class="header-title">{{ column.title }}</span>
            <VTextField v-model="selectedClient" :items="clients" item-title="name" item-value="id" clearable density="compact" hide-details variant="outlined" class="filter-select-outlined" placeholder="العميل" @update:search="searchClients" />
          </div>
        </template>

        <!-- Remaining Headers Standard -->
        <template v-for="h in ['created_at', 'total_amount', 'shipping_fee', 'commission_amount', 'company_amount', 'cod_amount', 'status', 'latest_status_note', 'actions']" #[`header.${h}`]="{ column }">
          <div class="header-filter justify-center"><span class="header-title">{{ column.title }}</span></div>
        </template>

        <!--    Item Slots -->
        <template #item.code="{ item }: { item: any }">
          <div class="d-flex flex-column text-xs py-1">
            <span class="text-primary font-weight-bold" style="white-space: nowrap;">#{{ item.code }}</span>
            <span v-if="item.external_code" class="text-disabled" style="white-space: nowrap;">{{ item.external_code }}</span>
          </div>
        </template>
        <template #item.shipper_date="{ item }: { item: any }">
          <div class="d-flex flex-column text-xs">
            <span class="text-primary font-weight-bold">{{ new Date(item.shipper_date).toLocaleDateString('en-GB') }}</span>
          </div>
        </template>
        <template #item.receiver_name="{ item }: { item: any }">
          <div class="d-flex flex-column align-center text-center justify-center" style="min-inline-size: 160px; padding-block: 4px;">
            <span class="font-weight-bold text-sm text-high-emphasis text-wrap mb-1">{{ item.receiver_name }}</span>
            <div class="d-flex align-center gap-1">
              <span class="text-xs text-secondary">{{ item.phone }} <template v-if="item.phone_2">/ {{ item.phone_2 }}</template></span>
            </div>
          </div>
        </template>
        <template #item.area="{ item }: { item: any }">
          <div class="d-flex flex-column text-xs" style="min-inline-size: 130px;">
            <span class="font-weight-medium">{{ item.governorate?.name }} / {{ item.city?.name }}</span>
            <span class="text-disabled text-wrap" style="max-inline-size: 150px;">{{ item.address }}</span>
          </div>
        </template>
        <template #item.total_amount="{ item }: { item: any }"><span class="font-weight-bold text-xs">{{ Number(item.total_amount).toFixed(0) }}</span></template>
        <template #item.shipping_fee="{ item }: { item: any }"><span class="text-xs">{{ Number(item.shipping_fee).toFixed(0) }}</span></template>
        <template #item.commission_amount="{ item }: { item: any }"><span class="text-xs">{{ Number(item.commission_amount || 0).toFixed(0) }}</span></template>
        <template #item.company_amount="{ item }: { item: any }"><span class="text-info font-weight-bold text-xs">{{ Number(item.company_amount || 0).toFixed(0) }}</span></template>
        <template #item.cod_amount="{ item }: { item: any }"><span class="text-success font-weight-bold text-xs">{{ Number(item.cod_amount).toFixed(0) }}</span></template>

        <template #item.status="{ item }: { item: any }">
          <VChip 
            size="x-small" 
            :color="resolveStatusColor(item.status)" 
            variant="tonal" 
            class="text-capitalize"
            style="font-size: 14px !important;"
            :class="(item.is_shipper_collected || !can('order.change-status' as any, 'all' as any)) ? 'cursor-not-allowed' : 'cursor-pointer'" 
            @click="(!item.is_shipper_collected && can('order.change-status' as any, 'all' as any)) && openStatusModal(item)"
          >
            {{ item.status.replace(/_/g, ' ') }}
          </VChip>
        </template>
        <template #item.order_note="{ item }: { item: any }"><span class="text-xs text-wrap" style=" display: block;max-inline-size: 150px;">{{ item.order_note || '—' }}</span></template>
        <template #item.latest_status_note="{ item }: { item: any }">
          <div v-if="item.latest_status_note" class="d-flex flex-wrap gap-1 py-1" style="max-inline-size: 250px;">
            <VChip
              v-for="(note, index) in item.latest_status_note.split(',')"
              :key="index"
              size="x-small"
              variant="tonal"
              :color="['secondary', 'primary', 'info'][index % 3]"
              class="text-xs"
              style="block-size: auto; min-block-size: 20px; padding-block: 4px; padding-inline: 8px; white-space: normal;"
            >
              {{ note.trim() }}
            </VChip>
          </div>
          <span v-else class="text-disabled">—</span>
        </template>

        <template #item.shipper_collection="{ item }: { item: any }">
          <VChip size="x-small" :color="getStateConfig(item.is_shipper_collected, false, item.shipper_collected_at).color" variant="elevated">{{ getStateConfig(item.is_shipper_collected, false, item.shipper_collected_at).label }}</VChip>
        </template>
        <template #item.shipper_return="{ item }: { item: any }">
          <VChip size="x-small" :color="getStateConfig(item.is_shipper_returned, item.is_in_shipper_return, item.shipper_returned_at).color" variant="elevated">{{ getStateConfig(item.is_shipper_returned, item.is_in_shipper_return, item.shipper_returned_at).label }}</VChip>
        </template>
        <template #item.has_return="{ item }: { item: any }">
          <VChip v-if="item.has_return" size="x-small" color="success" variant="elevated">{{ item.has_return_at ? new Date(item.has_return_at).toLocaleDateString('en-GB') : '✓' }}</VChip>
          <VChip v-else size="x-small" color="error" variant="elevated">✕</VChip>
        </template>
        <template #item.client_settlement="{ item }: { item: any }">
          <VChip size="x-small" :color="getStateConfig(item.is_client_settled, item.is_in_client_settlement, item.client_settled_at).color" variant="elevated">{{ getStateConfig(item.is_client_settled, item.is_in_client_settlement, item.client_settled_at).label }}</VChip>
        </template>
        <template #item.client_return="{ item }: { item: any }">
          <VChip size="x-small" :color="getStateConfig(item.is_client_returned, item.is_in_client_return, item.client_returned_at).color" variant="elevated">{{ getStateConfig(item.is_client_returned, item.is_in_client_return, item.client_returned_at).label }}</VChip>
        </template>

        <template #item.shipper="{ item }: { item: any }">
          <span v-if="!['DELIVERED', 'UNDELIVERED'].includes(item.status) && can('order.change-shipper' as any, 'all' as any)" class="text-xs text-primary cursor-pointer font-weight-medium" @click="openShipperModal(item)">{{ item.shipper?.name || 'Assign' }}</span>
          <span v-else class="text-xs text-disabled font-weight-medium">{{ item.shipper?.name || '—' }}</span>
        </template>
        <template #item.client="{ item }: { item: any }"><span class="text-xs font-weight-medium">{{ item.client?.name || '—' }}</span></template>
        <template #item.created_at="{ item }: { item: any }"><span class="text-xs text-disabled">{{ new Date(item.created_at).toLocaleDateString('en-GB') }}</span></template>

        <template #item.actions="{ item }: { item: any }">
          <div class="d-flex align-center gap-1">
            <template v-if="props.trashed === 'only'">
              <IconBtn
                v-if="can('order.delete' as any, 'all' as any)"
                size="small"
                @click="restoreOrder(resolveRowItem(item).id)"
              >
                <VIcon icon="tabler-refresh" />
                <VTooltip activator="parent" location="top">استعادة</VTooltip>
              </IconBtn>
              <IconBtn
                v-if="can('order.delete' as any, 'all' as any)"
                size="small"
                color="error"
                @click="forceDeleteOrder(resolveRowItem(item).id)"
              >
                <VIcon icon="tabler-trash" />
                <VTooltip activator="parent" location="top">حذف نهائي</VTooltip>
              </IconBtn>
            </template>
            <template v-else>
              <IconBtn
              v-if="can('activity-log.view' as any, 'all' as any)"
                size="small"
                color="primary"
                @click="openDetails(resolveRowItem(item))"
              >
                <VIcon icon="tabler-eye" />
                <VTooltip activator="parent" location="top">عرض التفاصيل والسجل</VTooltip>
              </IconBtn>
              <IconBtn
                v-if="can('order.view' as any, 'all' as any)"
                size="small"
                @click="printLabel(resolveRowItem(item).id)"
              >
                <VIcon icon="tabler-printer" />
                <VTooltip activator="parent" location="top">بوليصة الشحن</VTooltip>
              </IconBtn>
              <IconBtn
                v-if="can('order.view' as any, 'all' as any)"
                size="small"
                @click="printSmallLabel(resolveRowItem(item).id)"
              >
                <VIcon icon="tabler-tag" />
                <VTooltip activator="parent" location="top">ليبل صغير</VTooltip>
              </IconBtn>
              <VBtn
                icon
                size="x-small"
                color="default"
                variant="text"
              >
                <VIcon icon="tabler-dots-vertical" />
                <VMenu activator="parent">
                  <VList density="compact">
                    <VListItem
                      v-if="!resolveRowItem(item).is_shipper_collected && can('order.update' as any, 'all' as any)"
                      prepend-icon="tabler-edit"
                      title="تعديل"
                      @click="editOrder(resolveRowItem(item).id)"
                    />
                    <VListItem
                      v-if="!resolveRowItem(item).is_shipper_collected && can('order.delete' as any, 'all' as any)"
                      prepend-icon="tabler-trash"
                      title="حذف"
                      base-color="error"
                      @click="deleteOrder(resolveRowItem(item).id)"
                    />
                    <VListItem
                      prepend-icon="tabler-copy"
                      title="نسخ البيانات"
                      @click="copyOrderToClipboard(resolveRowItem(item))"
                    />
                    <VListItem
                      prepend-icon="tabler-brand-whatsapp"
                      title="واتساب العميل"
                      base-color="success"
                      @click="sendToWhatsApp(resolveRowItem(item))"
                    />
                    <VDivider class="my-1" />
                    <!-- Financial Actions -->
                    <VListItem
                      v-if="!resolveRowItem(item).is_shipper_collected && can('shipper-collection.create' as any, 'all' as any) && ['DELIVERED', 'UNDELIVERED'].includes(resolveRowItem(item).status)"
                      prepend-icon="tabler-cash"
                      title="تحصيل من الكابتن"
                      base-color="success"
                      :disabled="processingFinancialAction"
                      @click="toggleShipperCollection(resolveRowItem(item))"
                    />
                    <VListItem
                      v-if="resolveRowItem(item).is_shipper_collected && can('shipper-collection.update' as any, 'all' as any)"
                      prepend-icon="tabler-cash-off"
                      title="إلغاء التحصيل من الكابتن"
                      base-color="error"
                      :disabled="processingFinancialAction"
                      @click="toggleShipperCollection(resolveRowItem(item))"
                    />
                    <VListItem
                      v-if="!resolveRowItem(item).is_client_settled && can('client-settlement.create' as any, 'all' as any) && ['DELIVERED', 'UNDELIVERED'].includes(resolveRowItem(item).status)"
                      prepend-icon="tabler-mood-dollar"
                      title="تسوية مع العميل"
                      base-color="info"
                      :disabled="processingFinancialAction"
                      @click="toggleClientSettlement(resolveRowItem(item))"
                    />
                    <VListItem
                      v-if="resolveRowItem(item).is_client_settled && can('client-settlement.update' as any, 'all' as any)"
                      prepend-icon="tabler-mood-off"
                      title="إلغاء التسوية مع العميل"
                      base-color="error"
                      :disabled="processingFinancialAction"
                      @click="toggleClientSettlement(resolveRowItem(item))"
                    />
                    <VListItem
                      v-if="!resolveRowItem(item).is_shipper_returned && can('shipper-return.create' as any, 'all' as any) && ['DELIVERED', 'UNDELIVERED'].includes(resolveRowItem(item).status)"
                      prepend-icon="tabler-arrow-back-up"
                      title="مرتجع من الكابتن"
                      base-color="warning"
                      :disabled="processingFinancialAction"
                      @click="createShipperReturn(resolveRowItem(item))"
                    />
                    <VListItem
                      v-if="!resolveRowItem(item).is_client_returned && can('client-return.create' as any, 'all' as any) && resolveRowItem(item).has_return && resolveRowItem(item).is_shipper_returned"
                      prepend-icon="tabler-package-export"
                      title="مرتجع من العميل"
                      base-color="secondary"
                      :disabled="processingFinancialAction"
                      @click="createClientReturn(resolveRowItem(item))"
                    />
                  </VList>
                </VMenu>
              </VBtn>
            </template>
            <VIcon
              v-if="resolveRowItem(item).is_shipper_collected"
              icon="tabler-lock"
              size="14"
              color="secondary"
            />
          </div>
        </template>
        <template #bottom>
          <TablePagination 
            v-model:page="page" 
            v-model:itemsPerPage="itemsPerPage"
            :total-items="totalOrders" 
          />
        </template>
      </VDataTableServer>
    </VCard>

    <AddEditOrderModal
      v-model:is-dialog-visible="isAddEditOrderModalVisible"
      :order-id="editingOrderId"
      :metadata="pageMetadata"
      @order-saved="onOrderSaved"
    />
    <OrderStatusModal v-model:is-dialog-visible="isStatusModalVisible" :order="selectedOrderForStatus" :metadata="pageMetadata" @status-updated="onStatusUpdated" />
    <OrderShipperModal v-model:is-dialog-visible="isShipperModalVisible" :order="selectedOrderForShipper" :shippers="shippers" @shipper-updated="onShipperUpdated" />

    <BulkOrderStatusModal
      v-model:is-dialog-visible="isBulkStatusModalVisible"
      :selected-orders="selectedOrders"
      :metadata="pageMetadata"
      @status-updated="onBulkStatusUpdated"
    />
    <BulkOrderShipperModal
      v-model:is-dialog-visible="isBulkShipperModalVisible"
      :selected-orders="selectedOrders"
      :shippers="shippers"
      @shipper-updated="onBulkShipperUpdated"
    />

    <OrderDetailsModal
      v-model:is-dialog-visible="isDetailsModalVisible"
      :order="selectedOrderForDetails"
      @print="id => printLabel(id)"
    />
    <VDialog v-model="isImportErrorsModalVisible" max-width="700">
  <VCard>
    <VCardTitle class="d-flex align-center justify-space-between">
      <span>أخطاء الاستيراد ({{ importErrors.length }})</span>
      <IconBtn @click="isImportErrorsModalVisible = false">
        <VIcon icon="tabler-x" />
      </IconBtn>
    </VCardTitle>
    <VDivider />
    <VCardText style="max-block-size: 60vh; overflow-y: auto;">
      <VAlert
        v-for="(err, idx) in importErrors"
        :key="idx"
        type="error"
        variant="tonal"
        density="compact"
        class="mb-2 text-xs"
      >
        {{ err }}
      </VAlert>
    </VCardText>
    <VCardActions>
      <VSpacer />
      <VBtn color="secondary" variant="tonal" @click="isImportErrorsModalVisible = false">
        إغلاق
      </VBtn>
    </VCardActions>
  </VCard>
</VDialog>
  </section>
</template>

<style lang="scss" scoped>
.stats-card { transition: transform 0.2s; }
.stats-card:hover { transform: translateY(-2px); }

/* Fleet Style Timeline Exact Copy */
.fleet-timeline {
  :deep(.v-timeline-divider__dot) {
    background: rgb(var(--v-theme-surface)) !important;

    .v-timeline-divider__inner-dot {
      box-shadow: none !important;
    }
  }

  &.v-timeline .v-timeline-item:not(:last-child) {
    :deep(.v-timeline-item__body) {
      margin-block-end: 0.25rem;
    }
  }
}

.filter-table :deep(th),
.filter-table :deep(td) {
  border-inline-end: 1.5px solid  rgba(var(--v-border-color), 0.1) !important;
  border-inline-start: 1.5px solid  rgba(var(--v-border-color), 0.1) !important;
  text-align: center !important;
  vertical-align: middle !important;
}

.filter-table :deep(th) {
  background-color: var(--v-surface-variant) !important;
  border-block-end: 1px solid rgba(var(--v-border-color), 0.1) !important;
  padding-block: 8px 20px !important;
  vertical-align: top !important;
  white-space: nowrap;
}
.header-filter { display: flex; flex-direction: column; gap: 4px; margin-block-start: 4px; min-inline-size: 80px; }

.header-title {
  display: block;
  color: rgba(var(--v-theme-on-surface), 0.9);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.5px;
  margin-block-end: 2px;
  text-align: center;
}

.filter-input-outlined :deep(.v-field__input) {
  background-color: rgb(var(--v-theme-surface));
  color: var(--v-theme-primary) !important;
  font-size: 0.75rem !important;
  min-block-size: 28px !important;
  padding-block: 4px !important;
}

.filter-input-outlined :deep(.v-field__outline) {
  --v-field-border-opacity: 0.15;
}

.filter-select-outlined :deep(.v-field__input) {
  background-color: rgb(var(--v-theme-surface));
  color: var(--v-theme-primary);
  font-size: 0.75rem !important;
  min-block-size: 28px !important;
  padding-inline: 8px !important;
}

.filter-select-outlined :deep(.v-field__outline) {
  --v-field-border-opacity: 0.15;
}

.filter-table :deep(td) { font-size: 0.8rem !important; padding-block: 12px !important; padding-inline: 8px !important; }
.text-xs { font-size: 0.75rem !important; line-height: 1.2; }
.text-sm { font-size: 0.875rem !important; }

.custom-table-center-border th,
.custom-table-center-border td {
  box-sizing: border-box;
  border: 1.5px solid #bdbdbd !important;
  block-size: 100%;
  min-block-size: 48px;
  text-align: center !important;
  vertical-align: middle;
}

.custom-table-center-border thead th {
  background: #f8fafc;
  font-weight: bold;
}

.custom-table-center-border tr {
  block-size: 100%;
  min-block-size: 48px;
}

:deep(.row-flash) {
  animation: order-row-flash 2s ease-out;
}

@keyframes order-row-flash {
  0% {
    box-shadow: inset 0 0 0 2px rgb(var(--v-theme-primary));
  }

  100% {
    box-shadow: inset 0 0 0 0 transparent;
  }
}
</style>
