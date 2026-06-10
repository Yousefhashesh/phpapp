<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import AddEditPickupDrawer from '@/views/apps/operations/pickup/AddEditPickupDrawer.vue'
import { createUrl } from '@core/composable/createUrl'

const searchQuery = ref('')

// 👉 Fetching Pickups
const { data: pickupsData, execute: fetchPickups } = await useApi<any>(createUrl('/pickup-requests', {
  query: {
    q: searchQuery,
  },
}))

const pickups = computed(() => pickupsData.value || [])

// Headers
const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Client', key: 'client_id' },
  { title: 'Shipper', key: 'shipper_id' },
  { title: 'Date', key: 'pickup_date' },
  { title: 'Execution Status', key: 'status' },
  { title: 'Approval Status', key: 'approval_status' },
  { title: 'Cost', key: 'pickup_cost' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const { can } = useAbility()

const STORAGE_KEY = 'pickup-requests-visible-columns'

// Define permission mapping for columns
const columnPermissions: Record<string, string> = {
  client_id: 'pickup-request.column.client_id.view',
  shipper_id: 'pickup-request.column.shipper_id.view',
  pickup_date: 'pickup-request.column.pickup_date.view',
  status: 'pickup-request.column.status.view',
  approval_status: 'pickup-request.column.approval_status.view',
  pickup_cost: 'pickup-request.column.pickup_cost.view',
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
  if (status === 'ASSIGNED') return 'info'
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
const selectedPickup = ref<any>(null)

const openAddDrawer = () => {
  selectedPickup.value = null
  isAddEditDrawerVisible.value = true
}

const openEditDrawer = (pickup: any) => {
  selectedPickup.value = pickup
  isAddEditDrawerVisible.value = true
}

const deletePickup = async (id: number) => {
  if (confirm('Are you sure you want to delete this pickup request?')) {
    await $api(`/pickup-requests/${id}`, { method: 'DELETE' })
    fetchPickups()
  }
}

const handleFormSubmit = () => {
  fetchPickups()
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
              placeholder="Search Pickup"
            />
          </div>

          <VBtn
            v-if="can('pickup-request.create' as any, 'all' as any)"
            prepend-icon="tabler-plus"
            @click="openAddDrawer"
          >
            Add Pickup
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
        :items="pickups"
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

        <!-- Date -->
        <template #item.pickup_date="{ item }: { item: any }">
          <span class="text-sm">{{ item.pickup_date || '-' }}</span>
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

        <!-- Cost -->
        <template #item.pickup_cost="{ item }: { item: any }">
          <span class="text-sm">{{ item.pickup_cost }} EGP</span>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }: { item: any }">
          <div class="d-flex align-center gap-2">
            <IconBtn v-if="can('pickup-request.update' as any, 'all' as any)" size="small" @click="openEditDrawer(item)">
              <VIcon icon="tabler-edit" />
              <VTooltip activator="parent">Edit Pickup</VTooltip>
            </IconBtn>
            <IconBtn v-if="can('pickup-request.delete' as any, 'all' as any)" size="small" @click="deletePickup(item.id)">
              <VIcon icon="tabler-trash" />
              <VTooltip activator="parent">Delete Pickup</VTooltip>
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <AddEditPickupDrawer
      v-model:isDrawerOpen="isAddEditDrawerVisible"
      :pickup="selectedPickup"
      @submit="handleFormSubmit"
    />
  </section>
</template>
