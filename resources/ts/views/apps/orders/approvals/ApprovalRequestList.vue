<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import { createUrl } from '@core/composable/createUrl'
import AddEditOrderModal from '../components/AddEditOrderModal.vue'
import OrderStatusModal from '../components/OrderStatusModal.vue'

const isAddEditOrderModalVisible = ref(false)
const isStatusModalVisible = ref(false)
const editingOrderId = ref<number | null>(null)
const selectedOrderForStatus = ref<any>(null)

const searchQuery = ref('')
const selectedStatus = ref<string | null>(null)
const selectedApprovalStatus = ref<string | null>('PENDING')
const itemsPerPage = ref(50)
const page = ref(1)

const openStatusModal = (order: any) => {
  selectedOrderForStatus.value = order
  isStatusModalVisible.value = true
}

// 👉 Headers
const headers = [
  { title: 'CODE', key: 'code' },
  { title: 'RECEIVER', key: 'receiver_name' },
  { title: 'AREA', key: 'area' },
  { title: 'TOTAL', key: 'total_amount' },
  { title: 'APPROVAL', key: 'approval_status' },
  { title: 'SHIPPER', key: 'shipper' },
  { title: 'CLIENT', key: 'client' },
  { title: 'DATE', key: 'created_at' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

// 👉 Filters State
const selectedGovernorate = ref<number | null>(null)
const selectedShipper = ref<number | null>(null)
const selectedClient = ref<number | null>(null)

// 👉 Fetching Filter Options
const governorates = ref<any[]>([])
const shippers = ref<any[]>([])
const clients = ref<any[]>([])

const fetchFilters = async () => {
    try {
        const { data: govData } = await useApi<any>('/governorates').get().json()
        governorates.value = govData.value?.data || govData.value || []

        const { data: shipData } = await useApi<any>('/shippers').get().json()
        shippers.value = shipData.value?.data || []

        const { data: cliData } = await useApi<any>('/clients').get().json()
        clients.value = cliData.value?.data || []
  } catch (e) { /*  */ }
}

// 👉 Fetching Orders
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

watch([searchQuery, selectedStatus, selectedApprovalStatus, selectedGovernorate, selectedShipper, selectedClient, itemsPerPage, page], () => {
  fetchOrders()
})

const approveOrder = async (id: number) => {
  if (confirm('Approve this order?')) {
    try {
      const response = await useApi(`/orders/${id}/approve`).patch({}).json()
      if (response.error.value) {
        alert('Error: ' + (response.error.value.message || 'Approval failed'))
      } else {
        fetchOrders()
      }
    } catch (e) {
      console.error('Approve error:', e)
      alert('Network Error during approval')
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
        alert('Error: ' + (response.error.value.message || 'Rejection failed'))
      } else {
        fetchOrders()
      }
    } catch (e) {
      console.error('Reject error:', e)
      alert('Network Error during rejection')
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

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items="orders"
        :items-length="totalOrders"
        :headers="headers"
        class="text-no-wrap"
      >
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
          <div class="d-flex flex-column">
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
  </section>
</template>
