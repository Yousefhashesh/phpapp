<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import { createUrl } from '@core/composable/createUrl'
import AddEditOrderModal from '../components/AddEditOrderModal.vue'
import OrderStatusModal from '../components/OrderStatusModal.vue'
import BulkOrderStatusModal from '../components/BulkOrderStatusModal.vue'
import { useNotificationStore } from '@/stores/useNotificationStore'

const isAddEditOrderModalVisible = ref(false)
const isStatusModalVisible = ref(false)
const isBulkStatusModalVisible = ref(false)
const editingOrderId = ref<number | null>(null)
const selectedOrderForStatus = ref<any>(null)

const searchQuery = ref('')
const selectedStatus = ref<string | null>(null)
const selectedApprovalStatus = ref<string | null>('PENDING')
const itemsPerPage = ref(50)
const page = ref(1)

const selectedOrders = ref<any[]>([])
const notificationStatus = useNotificationStore()
const notify = (msg: string, color: string = 'success') => {
  notificationStatus.notify(msg, color)
}

const filters = ref({
  code: '',
  receiver_name: '',
  address: '',
  order_note: '',
})

const openStatusModal = (order: any) => {
  selectedOrderForStatus.value = order
  isStatusModalVisible.value = true
}

//    Headers
const headers = [
  { title: 'CODE', key: 'code' },
  { title: 'RECEIVER', key: 'receiver_name' },
  { title: 'AREA', key: 'area' },
  { title: 'TOTAL', key: 'total_amount' },
  { title: 'APPROVAL', key: 'approval_status' },
  { title: 'SHIPPER', key: 'shipper' },
  { title: 'CLIENT', key: 'client' },
  { title: 'ORDER NOTE', key: 'order_note' },
  { title: 'DATE', key: 'created_at' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

//    Filters State
const selectedGovernorate = ref<number | null>(null)
const selectedShipper = ref<number | null>(null)
const selectedClient = ref<number | null>(null)

//    Fetching Filter Options
const governorates = ref<any[]>([])
const shippers = ref<any[]>([])
const clients = ref<any[]>([])

const fetchFilters = async () => {
    try {
        const { data: govData } = await useApi<any>('/governorates').get().json()
        governorates.value = govData.value?.data || govData.value || []

        const { data: shipData } = await useApi<any>('/shippers?per_page=500').get().json()
        shippers.value = (shipData.value?.data || []).map((s: any) => ({
          id: s.user_id,
          name: s.user?.name || 'Unknown',
        }))

        const { data: cliData } = await useApi<any>('/clients?per_page=500').get().json()
        clients.value = (cliData.value?.data || []).map((c: any) => ({
          id: c.user_id,
          name: c.user?.name || 'Unknown',
        }))
  } catch (e) { /*  */ }
}

//    Fetching Orders
const orders = ref<any[]>([])
const totalOrders = ref(0)
const ordersLoading = ref(false)

const fetchOrders = async () => {
  ordersLoading.value = true
  try {
    const { data: oData } = await useApi<any>(createUrl('/orders', {
      query: {
        q: searchQuery,
        status: selectedStatus,
        approval_status: selectedApprovalStatus,
        governorate_id: selectedGovernorate,
        shipper_user_id: selectedShipper,
        client_user_id: selectedClient,
        per_page: itemsPerPage,
        page,
        ...filters.value,
      },
    })).get().json()
    orders.value = oData.value?.data || []
    totalOrders.value = oData.value?.total || 0
  } catch (e) { console.error('Orders fetch error:', e) }
  ordersLoading.value = false
}

onMounted(() => {
    fetchFilters()
    fetchOrders()
})

watch([searchQuery, selectedStatus, selectedApprovalStatus, selectedGovernorate, selectedShipper, selectedClient, filters], () => {
  page.value = 1
  fetchOrders()
}, { deep: true })

watch([page, itemsPerPage], () => {
  fetchOrders()
})

const approveOrder = async (id: number) => {
  if (confirm('Approve this order?')) {
    try {
      const response = await useApi(`/orders/${id}/approve`).patch({}).json()
      if (response.error.value) {
        notify('Error: ' + (response.error.value.message || 'Approval failed'), 'error')
      } else {
        notify('Order approved successfully', 'success')
        fetchOrders()
      }
    } catch (e) {
      console.error('Approve error:', e)
      notify('Network Error during approval', 'error')
    }
  }
}

const rejectOrder = async (id: number) => {
  const note = prompt('Rejection reason (Optional):')
  if (note !== null) {
    try {
      const response = await useApi(`/orders/${id}/reject`).patch({ 
        approval_note: note 
      }).json()
      if (response.error.value) {
        notify('Error: ' + (response.error.value.message || 'Rejection failed'), 'error')
      } else {
        notify('Order rejected successfully', 'success')
        fetchOrders()
      }
    } catch (e) {
      console.error('Reject error:', e)
      notify('Network Error during rejection', 'error')
    }
  }
}

const bulkApproveOrders = async () => {
  if (!selectedOrders.value.length) return
  if (confirm(`Approve ${selectedOrders.value.length} selected orders?`)) {
    try {
      const ids = selectedOrders.value.map(o => o.id)
      const response = await useApi('/orders/bulk-approve').patch({ order_ids: ids }).json()
      if (response.error.value) {
        notify('Error: ' + (response.error.value.message || 'Bulk approval failed'), 'error')
      } else {
        notify('Bulk approval completed successfully', 'success')
        selectedOrders.value = []
        fetchOrders()
      }
    } catch (e) {
      console.error('Bulk approve error:', e)
      notify('Network Error during bulk approval', 'error')
    }
  }
}

const bulkRejectOrders = async () => {
  if (!selectedOrders.value.length) return
  const note = prompt(`Reject ${selectedOrders.value.length} selected orders? Rejection reason (Optional):`)
  if (note !== null) {
    try {
      const ids = selectedOrders.value.map(o => o.id)
      const response = await useApi('/orders/bulk-reject').patch({ 
        order_ids: ids,
        approval_note: note 
      }).json()
      if (response.error.value) {
        notify('Error: ' + (response.error.value.message || 'Bulk rejection failed'), 'error')
      } else {
        notify('Bulk rejection completed successfully', 'success')
        selectedOrders.value = []
        fetchOrders()
      }
    } catch (e) {
      console.error('Bulk reject error:', e)
      notify('Network Error during bulk rejection', 'error')
    }
  }
}

const editOrder = (id: number) => {
  editingOrderId.value = id
  isAddEditOrderModalVisible.value = true
}

const deleteOrder = async (id: number) => {
  if (confirm('Delete this order?')) {
    try {
      await useApi(`/orders/${id}`).delete().json()
      fetchOrders()
    } catch (e) { console.error('Delete error:', e) }
  }
}
</script>

<template>
  <section>
    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="12" md="3">
            <AppTextField v-model="searchQuery" placeholder="Search..." prepend-inner-icon="tabler-search" @keyup.enter="fetchOrders" />
          </VCol>
          <VCol cols="12" md="2">
            <AppSelect
              v-model="selectedApprovalStatus"
              placeholder="Approval Status"
              :items="[
                { title: 'Pending', value: 'PENDING' },
                { title: 'Rejected', value: 'REJECTED' },
              ]"
            />
          </VCol>
          <VCol cols="12" md="2">
            <AppSelect v-model="selectedGovernorate" placeholder="Governorate" :items="governorates" item-title="name" item-value="id" clearable />
          </VCol>
          <VCol cols="12" md="2">
            <VBtn variant="tonal" color="secondary" block @click="searchQuery = ''; selectedApprovalStatus = 'PENDING'; selectedGovernorate = null">Reset</VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VCard :loading="ordersLoading">
      <VCardText class="d-flex flex-wrap gap-x-4 align-center">
        <span class="text-h6">Orders Approval Requests</span>
      </VCardText>
      <VDivider />

      <!-- 📦 Bulk Actions Row (Header Position) -->
      <VCardText v-show="selectedOrders.length" class="bg-light-primary py-2 border-bottom border-top rounded-0">
        <div class="d-flex align-center gap-4 flex-wrap">
          <div class="text-subtitle-2 text-primary font-weight-bold">
            <VChip color="primary" size="small" class="me-2">{{ selectedOrders.length }}</VChip>
            Elements Selected
          </div>
          <VDivider vertical class="mx-2" />
          <VBtn size="small" color="success" variant="elevated" prepend-icon="tabler-check" @click="bulkApproveOrders">Approve Selected</VBtn>
          <VBtn size="small" color="error" variant="elevated" prepend-icon="tabler-x" @click="bulkRejectOrders">Reject Selected</VBtn>
          <VBtn size="small" color="primary" variant="elevated" prepend-icon="tabler-settings" @click="isBulkStatusModalVisible = true">Change Status</VBtn>
          <VSpacer />
          <VBtn icon size="x-small" variant="text" color="secondary" @click="selectedOrders = []"><VIcon icon="tabler-x" /></VBtn>
        </div>
      </VCardText>

      <VDataTableServer
        v-model="selectedOrders"
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items="orders"
        :items-length="totalOrders"
        :headers="headers"
        item-value="id"
        return-object
        show-select
        class="text-no-wrap filter-table"
      >
        <!--    Header Filter Slots -->
        <template #header.code="{ column }">
          <div class="header-filter">
            <span class="header-title">{{ column.title }}</span>
            <VTextField v-model="filters.code" density="compact" hide-details variant="outlined" placeholder="بحث..." class="filter-input-outlined" />
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
        <template #header.shipper="{ column }">
          <div class="header-filter"><span class="header-title">{{ column.title }}</span>
            <VSelect v-model="selectedShipper" :items="shippers" item-title="name" item-value="id" clearable density="compact" hide-details variant="outlined" class="filter-select-outlined" placeholder="المندوب" />
          </div>
        </template>
        <template #header.client="{ column }">
          <div class="header-filter"><span class="header-title">{{ column.title }}</span>
            <VSelect v-model="selectedClient" :items="clients" item-title="name" item-value="id" clearable density="compact" hide-details variant="outlined" class="filter-select-outlined" placeholder="العميل" />
          </div>
        </template>
        <template #header.order_note="{ column }">
          <div class="header-filter">
            <span class="header-title">{{ column.title }}</span>
            <VTextField v-model="filters.order_note" density="compact" hide-details variant="outlined" placeholder="ملاحظات" class="filter-input-outlined" />
          </div>
        </template>

        <!-- Remaining Headers Standard -->
        <template v-for="h in ['total_amount', 'approval_status', 'created_at', 'actions']" #[`header.${h}`]="{ column }">
          <div class="header-filter justify-center"><span class="header-title">{{ column.title }}</span></div>
        </template>

        <template #item.code="{ item }: { item: any }">
          <span class="text-h6 text-primary font-weight-bold">#{{ item.code }}</span>
        </template>
        <template #item.receiver_name="{ item }: { item: any }">
           <div class="d-flex flex-column text-start">
             <span class="text-base text-high-emphasis font-weight-medium text-wrap">{{ item.receiver_name }}</span>
             <span class="text-sm text-disabled">{{ item.phone }}</span>
           </div>
        </template>
        <template #item.approval_status="{ item }: { item: any }">
          <div class="cursor-pointer" @click="openStatusModal(item)">
            <VChip size="small" :color="item.approval_status === 'REJECTED' ? 'error' : 'warning'" variant="tonal" class="text-capitalize">
              {{ item.approval_status }}
            </VChip>
            <div v-if="item.approval_note" class="text-xs text-error mt-1 text-wrap">{{ item.approval_note }}</div>
          </div>
        </template>
        <template #item.area="{ item }: { item: any }">
          <div class="d-flex flex-column text-start">
             <span class="text-sm">{{ item.governorate?.name || '-' }}</span>
             <span class="text-xs text-disabled">{{ item.city?.name || '-' }}</span>
           </div>
        </template>
        <template #item.shipper="{ item }: { item: any }">
           <span class="text-sm">{{ item.shipper?.name || 'N/A' }}</span>
        </template>
        <template #item.client="{ item }: { item: any }">
           <span class="text-sm">{{ item.client?.name || 'N/A' }}</span>
        </template>
        <template #item.order_note="{ item }: { item: any }">
          <span class="text-xs text-wrap" style="display: block; max-inline-size: 150px;">{{ item.order_note || '—' }}</span>
        </template>
        <template #item.created_at="{ item }: { item: any }">
          <span class="text-sm">{{ new Date(item.created_at).toLocaleDateString() }}</span>
        </template>

        <template #item.actions="{ item }: { item: any }">
          <div class="d-flex gap-1" v-if="item.approval_status === 'PENDING'">
            <VBtn size="x-small" color="success" variant="elevated" @click="approveOrder(item.id)">Approve</VBtn>
            <VBtn size="x-small" color="error" variant="elevated" @click="rejectOrder(item.id)">Reject</VBtn>
          </div>
          <div v-else class="text-xs text-disabled">Processed</div>
        </template>

        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalOrders" />
        </template>
      </VDataTableServer>
    </VCard>

    <AddEditOrderModal
      v-model:is-dialog-visible="isAddEditOrderModalVisible"
      :order-id="editingOrderId"
      @order-saved="fetchOrders"
    />
    <OrderStatusModal
      v-model:is-dialog-visible="isStatusModalVisible"
      :order="selectedOrderForStatus"
      @status-updated="fetchOrders"
    />
    <BulkOrderStatusModal
      v-model:is-dialog-visible="isBulkStatusModalVisible"
      :selected-orders="selectedOrders"
      @status-updated="() => { fetchOrders(); selectedOrders = [] }"
    />
  </section>
</template>

<style lang="scss" scoped>
.filter-table :deep(th),
.filter-table :deep(td) {
  border-inline-end: 1.5px solid rgba(var(--v-border-color), 0.1) !important;
  border-inline-start: 1.5px solid rgba(var(--v-border-color), 0.1) !important;
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
</style>
