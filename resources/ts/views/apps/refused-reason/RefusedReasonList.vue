<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import AddEditRefusedReasonDrawer from '@/views/apps/refused-reason/AddEditRefusedReasonDrawer.vue'
import { createUrl } from '@core/composable/createUrl'

// Headers
const headers = [
  { title: 'Reason', key: 'reason' },
  { title: 'Status Map', key: 'status' },
  { title: 'Active', key: 'is_active' },
  { title: 'Clear', key: 'is_clear' },
  { title: 'Edit Amount', key: 'is_edit_amount' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const searchQuery = ref('')
const { can } = useAbility()

const STORAGE_KEY = 'refused-reasons-visible-columns'

// Define permission mapping for columns
const columnPermissions: Record<string, string> = {
  reason: 'operations.refused-reason.column.reason.view',
  status: 'operations.refused-reason.column.status.view',
  is_active: 'operations.refused-reason.column.is_active.view',
  is_clear: 'operations.refused-reason.column.is_clear.view',
  is_edit_amount: 'operations.refused-reason.column.is_edit_amount.view',
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

// 👉 Fetching Refused Reasons
const { data: refusedReasonsData, execute: fetchRefusedReasons } = await useApi<any>(createUrl('/refused-reasons', {
  query: {
    q: searchQuery,
  },
}))

const refusedReasons = computed(() => refusedReasonsData.value || [])

const isAddEditDrawerVisible = ref(false)
const selectedRefusedReason = ref<any>(null)

const openAddDrawer = () => {
  selectedRefusedReason.value = null
  isAddEditDrawerVisible.value = true
}

const openEditDrawer = (reason: any) => {
  selectedRefusedReason.value = reason
  isAddEditDrawerVisible.value = true
}

const deleteRefusedReason = async (id: number) => {
  if (confirm('Are you sure you want to delete this reason?')) {
    await $api(`/refused-reasons/${id}`, { method: 'DELETE' })
    fetchRefusedReasons()
  }
}

const handleFormSubmit = () => {
  fetchRefusedReasons()
}

const resolveStatusVariant = (status: string) => {
  if (status === 'OUT_FOR_DELIVERY') return { color: 'info', icon: 'tabler-truck' }
  if (status === 'DELIVERED') return { color: 'success', icon: 'tabler-circle-check' }
  if (status === 'HOLD') return { color: 'warning', icon: 'tabler-clock' }
  if (status === 'UNDELIVERED') return { color: 'error', icon: 'tabler-circle-x' }
  return { color: 'secondary', icon: 'tabler-help-circle' }
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
              placeholder="Search Reason"
            />
          </div>

          <VBtn
            v-if="can('operations.refused-reason.create' as any, 'all' as any)"
            prepend-icon="tabler-plus"
            @click="openAddDrawer"
          >
            Add Reason
          </VBtn>

          <!-- 👉 Column Visibility Toggle -->
          <VMenu :close-on-content-click="false">
            <template #activator="{ props }">
              <VBtn icon variant="tonal" color="secondary" v-bind="props">
                <VIcon icon="tabler-list" />
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
        :items="refusedReasons"
        :headers="activeHeaders"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <!-- Reason -->
        <template #item.reason="{ item }: { item: any }">
          <span class="text-h6 font-weight-medium">{{ item.reason }}</span>
        </template>

        <!-- Status -->
        <template #item.status="{ item }: { item: any }">
          <VChip
            size="small"
            :color="resolveStatusVariant(item.status).color"
            class="text-capitalize"
          >
            <template #prepend>
              <VIcon 
                :icon="resolveStatusVariant(item.status).icon" 
                size="16"
                class="me-1"
              />
            </template>
            {{ item.status.replace(/_/g, ' ') }}
          </VChip>
        </template>

        <!-- Switches -->
        <template #item.is_active="{ item }: { item: any }">
          <VIcon
            :icon="item.is_active ? 'tabler-circle-check' : 'tabler-circle-x'"
            :color="item.is_active ? 'success' : 'error'"
          />
        </template>
        
        <template #item.is_clear="{ item }: { item: any }">
          <VIcon
            :icon="item.is_clear ? 'tabler-circle-check' : 'tabler-circle-x'"
            :color="item.is_clear ? 'success' : 'error'"
          />
        </template>
        
        
        <template #item.is_edit_amount="{ item }: { item: any }">
          <VIcon
            :icon="item.is_edit_amount ? 'tabler-circle-check' : 'tabler-circle-x'"
            :color="item.is_edit_amount ? 'success' : 'error'"
          />
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }: { item: any }">
          <div class="d-flex align-center gap-2">
            <IconBtn 
              v-if="can('operations.refused-reason.update' as any, 'all' as any)"
              size="small" 
              @click="openEditDrawer(item)"
            >
              <VIcon icon="tabler-pencil" />
              <VTooltip activator="parent">Edit Reason</VTooltip>
            </IconBtn>
            <IconBtn 
              v-if="can('operations.refused-reason.delete' as any, 'all' as any)"
              size="small" 
              @click="deleteRefusedReason(item.id)"
            >
              <VIcon icon="tabler-trash" />
              <VTooltip activator="parent">Delete Reason</VTooltip>
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <AddEditRefusedReasonDrawer
      v-model:isDrawerOpen="isAddEditDrawerVisible"
      :refused-reason="selectedRefusedReason"
      @submit="handleFormSubmit"
    />
  </section>
</template>
