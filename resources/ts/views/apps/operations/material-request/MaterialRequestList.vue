<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import AddEditMaterialRequestDrawer from '@/views/apps/operations/material-request/AddEditMaterialRequestDrawer.vue'
import { createUrl } from '@core/composable/createUrl'

// Headers
const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Client', key: 'client_id' },
  { title: 'Shipper', key: 'shipper_id' },
  { title: 'Materials', key: 'materials_total' },
  { title: 'Shipping', key: 'shipping_cost' },
  { title: 'Delivery', key: 'delivery_type' },
  { title: 'Execution', key: 'status' },
  { title: 'Approval', key: 'approval_status' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const searchQuery = ref('')

// 👉 Fetching Material Requests
const { data: requestsData, execute: fetchRequests } = await useApi<any>(createUrl('/material-requests', {
  query: {
    q: searchQuery,
  },
}))

const requests = computed(() => requestsData.value || [])

const { can } = useAbility()

const STORAGE_KEY = 'material-requests-visible-columns'

// Define permission mapping for columns
const columnPermissions: Record<string, string> = {
  client_id: 'material-request.column.client_id.view',
  shipper_id: 'material-request.column.shipper_id.view',
  materials_total: 'material-request.column.materials_total.view',
  shipping_cost: 'material-request.column.shipping_cost.view',
  delivery_type: 'material-request.column.delivery_type.view',
  status: 'material-request.column.status.view',
  approval_status: 'material-request.column.approval_status.view',
}

const visibleHeaderKeys = ref(JSON.parse(localStorage.getItem(STORAGE_KEY) || JSON.stringify(headers.map(h => h.key))))

const activeHeaders = computed(() => {
  return headers.filter(h => {
    // 1. Check user manual visibility
    if (!visibleHeaderKeys.value.includes(h.key)) return false
    
    // 2. Check permission
    const perm = columnPermissions[h.key]
    if (perm && !can(perm as any, 'all' as any)) return false
    
    return true
  })
})

const filteredHeadersForMenu = computed(() => {
  return headers.filter(h => {
    const perm = columnPermissions[h.key]
    return !perm || can(perm as any, 'all' as any)
  })
})

watch(visibleHeaderKeys, (newVal) => {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal))
})

const resolveStatusColor = (status: string) => {
  if (status === 'COMPLETED') return 'success'
  if (status === 'PROCESSING') return 'info'
  if (status === 'PENDING') return 'warning'
  if (status === 'CANCELLED') return 'error'
  return 'secondary'
}

const resolveApprovalColor = (status: string) => {
  if (status === 'APPROVED') return 'success'
  if (status === 'PENDING') return 'warning'
  if (status === 'REJECTED') return 'error'
  return 'secondary'
}

const isAddEditDrawerVisible = ref(false)
const selectedRequest = ref<any>(null)

const openAddDrawer = () => {
  selectedRequest.value = null
  isAddEditDrawerVisible.value = true
}

const openEditDrawer = (request: any) => {
  selectedRequest.value = request
  isAddEditDrawerVisible.value = true
}

const deleteRequest = async (id: number) => {
  if (confirm('Are you sure you want to delete this demand?')) {
    await $api(`/material-requests/${id}`, { method: 'DELETE' })
    fetchRequests()
  }
}

const handleFormSubmit = () => {
  fetchRequests()
}
</script>

<template>
  <section>
    <VCard>
      <!-- 👉 Search & Adds -->
      <VCardText class="d-flex flex-wrap gap-4">
        <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
          <div style="inline-size: 15.625rem;">
            <AppTextField
              v-model="searchQuery"
              placeholder="Search Request"
            />
          </div>

          <VBtn
            v-if="can('material-request.create' as any, 'all' as any)"
            prepend-icon="tabler-plus"
            @click="openAddDrawer"
          >
            Add Request
          </VBtn>

          <!-- 👉 Column Visibility Toggle -->
          <VMenu :close-on-content-click="false">
            <template #activator="{ props }">
              <VBtn icon variant="tonal" color="secondary" v-bind="props">
                <VIcon icon="tabler-layout-columns" />
              </VBtn>
            </template>
            <VList class="pa-2">
              <VListItem v-for="h in filteredHeadersForMenu" :key="h.key" density="compact">
                <VCheckbox v-model="visibleHeaderKeys" :value="h.key" :label="h.title" hide-details density="compact" />
              </VListItem>
            </VList>
          </VMenu>
        </div>
      </VCardText>

      <VDivider />

      <VDataTable
        :items="requests"
        :headers="activeHeaders"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <!-- ID -->
        <template #item.id="{ item }: { item: any }">
          <span class="text-sm font-weight-bold">#{{ item.id }}</span>
        </template>

        <!-- Client -->
        <template #item.client_id="{ item }: { item: any }">
          <span class="text-sm">{{ item.client?.name || '-' }}</span>
        </template>

        <!-- Shipper -->
        <template #item.shipper_id="{ item }: { item: any }">
          <span class="text-sm">{{ item.shipper?.name || '-' }}</span>
        </template>

        <!-- Material Cost with details in tooltip -->
        <template #item.materials_total="{ item }: { item: any }">
          <div class="d-flex flex-column">
             <span class="text-sm font-weight-bold">{{ item.materials_total }} EGP</span>
             <small class="text-xs text-muted">{{ item.items?.length || 0 }} Items</small>
             <VTooltip activator="parent" location="bottom">
                <div v-for="ai in item.items" :key="ai.id">
                   - {{ ai.material?.name }}: {{ ai.quantity }} x {{ ai.price }}
                </div>
             </VTooltip>
          </div>
        </template>

        <!-- Shipping -->
        <template #item.shipping_cost="{ item }: { item: any }">
          <span class="text-sm">{{ item.shipping_cost }} EGP</span>
        </template>

        <!-- Delivery Type -->
        <template #item.delivery_type="{ item }: { item: any }">
          <VChip size="small" variant="tonal" :color="item.delivery_type === 'PICKUP' ? 'secondary' : 'primary'">
            {{ item.delivery_type }}
          </VChip>
        </template>

        <!-- Execution Status -->
        <template #item.status="{ item }: { item: any }">
          <VChip
            size="small"
            :color="resolveStatusColor(item.status)"
            variant="tonal"
          >
            {{ item.status }}
          </VChip>
        </template>

        <!-- Approval Status -->
        <template #item.approval_status="{ item }: { item: any }">
          <VChip
            size="small"
            :color="resolveApprovalColor(item.approval_status)"
            variant="tonal"
          >
            {{ item.approval_status }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }: { item: any }">
          <div class="d-flex align-center gap-2">
            <IconBtn v-if="can('material-request.update' as any, 'all' as any)" size="small" @click="openEditDrawer(item)">
              <VIcon icon="tabler-edit" />
              <VTooltip activator="parent">Edit Request</VTooltip>
            </IconBtn>
            <IconBtn v-if="can('material-request.delete' as any, 'all' as any)" size="small" @click="deleteRequest(item.id)">
              <VIcon icon="tabler-trash" />
              <VTooltip activator="parent">Delete Request</VTooltip>
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <AddEditMaterialRequestDrawer
      v-model:isDrawerOpen="isAddEditDrawerVisible"
      :request="selectedRequest"
      @submit="handleFormSubmit"
    />
  </section>
</template>
