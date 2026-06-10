<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import AddEditVisitDrawer from '@/views/apps/operations/visit/AddEditVisitDrawer.vue'
import { createUrl } from '@core/composable/createUrl'

// Headers
const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Shipper', key: 'shipper_id' },
  { title: 'Client', key: 'client_id' },
  { title: 'Linked Pickup', key: 'pickup_request_id' },
  { title: 'Linked Material', key: 'material_request_id' },
  { title: 'Cost', key: 'visit_cost' },
  { title: 'Date', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const searchQuery = ref('')

// 👉 Fetching Visits
const { data: visitsData, execute: fetchVisits } = await useApi<any>(createUrl('/visits', {
  query: {
    q: searchQuery,
  },
}))

const visits = computed(() => visitsData.value || [])

const { can } = useAbility()

const STORAGE_KEY = 'visits-visible-columns'

// Define permission mapping for columns
const columnPermissions: Record<string, string> = {
  shipper_id: 'visit.column.shipper_id.view',
  client_id: 'visit.column.client_id.view',
  pickup_request_id: 'visit.column.pickup_request_id.view',
  material_request_id: 'visit.column.material_request_id.view',
  visit_cost: 'visit.column.visit_cost.view',
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

const isAddEditDrawerVisible = ref(false)
const selectedVisit = ref<any>(null)

const openAddDrawer = () => {
  selectedVisit.value = null
  isAddEditDrawerVisible.value = true
}

const openEditDrawer = (visit: any) => {
  selectedVisit.value = visit
  isAddEditDrawerVisible.value = true
}

const deleteVisit = async (id: number) => {
  if (confirm('Are you sure you want to delete this visit?')) {
    await $api(`/visits/${id}`, { method: 'DELETE' })
    fetchVisits()
  }
}

const handleFormSubmit = () => {
  fetchVisits()
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
              placeholder="Search Visit"
            />
          </div>

          <VBtn
            v-if="can('visit.create' as any, 'all' as any)"
            prepend-icon="tabler-plus"
            @click="openAddDrawer"
          >
            Add Visit
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
        :items="visits"
        :headers="activeHeaders"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <!-- ID -->
        <template #item.id="{ item }: { item: any }">
          <span class="text-sm font-weight-bold">#{{ item.id }}</span>
        </template>

        <!-- Shipper / Client -->
        <template #item.shipper_id="{ item }: { item: any }">
          <span class="text-sm">{{ item.shipper?.name || '-' }}</span>
        </template>
        <template #item.client_id="{ item }: { item: any }">
          <span class="text-sm">{{ item.client?.name || '-' }}</span>
        </template>

        <!-- Linked Requests -->
        <template #item.pickup_request_id="{ item }: { item: any }">
          <VChip v-if="item.pickup_request_id" size="small" variant="tonal" color="info">
            Pickup #{{ item.pickup_request_id }}
          </VChip>
          <span v-else>-</span>
        </template>
        <template #item.material_request_id="{ item }: { item: any }">
          <VChip v-if="item.material_request_id" size="small" variant="tonal" color="info">
            Material #{{ item.material_request_id }}
          </VChip>
          <span v-else>-</span>
        </template>

        <!-- Cost -->
        <template #item.visit_cost="{ item }: { item: any }">
          <span class="text-sm">{{ item.visit_cost }} EGP</span>
        </template>

        <!-- Date -->
        <template #item.created_at="{ item }: { item: any }">
          <span class="text-sm">{{ new Date(item.created_at).toLocaleString() }}</span>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }: { item: any }">
          <div class="d-flex align-center gap-2">
            <IconBtn v-if="can('visit.update' as any, 'all' as any)" size="small" @click="openEditDrawer(item)">
              <VIcon icon="tabler-edit" />
              <VTooltip activator="parent">Edit Visit</VTooltip>
            </IconBtn>
            <IconBtn v-if="can('visit.delete' as any, 'all' as any)" size="small" @click="deleteVisit(item.id)">
              <VIcon icon="tabler-trash" />
              <VTooltip activator="parent">Delete Visit</VTooltip>
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <AddEditVisitDrawer
      v-model:isDrawerOpen="isAddEditDrawerVisible"
      :visit="selectedVisit"
      @submit="handleFormSubmit"
    />
  </section>
</template>
