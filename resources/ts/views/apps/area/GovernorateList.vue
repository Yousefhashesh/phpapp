<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import AddEditGovernorateDrawer from '@/views/apps/area/AddEditGovernorateDrawer.vue'
import { createUrl } from '@core/composable/createUrl'

// Headers
const headers = [
  { title: 'Area', key: 'name' },
  { title: 'Follow-up Hours', key: 'follow_up_hours' },
  { title: 'Default Shipper', key: 'defaultShipper' },
  { title: 'Cities', key: 'cities' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const searchQuery = ref('')
const { can } = useAbility()

const STORAGE_KEY = 'areas-visible-columns'

// Define permission mapping for columns
const columnPermissions: Record<string, string> = {
  name: 'area.column.name.view',
  follow_up_hours: 'area.column.follow_up_hours.view',
  defaultShipper: 'area.column.default_shipper.view',
  cities: 'area.column.cities.view',
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

// 👉 Fetching Governorates
const { data: governoratesData, execute: fetchGovernorates } = await useApi<any>(createUrl('/governorates', {
  query: {
    q: searchQuery,
  },
}))

const governorates = computed(() => governoratesData.value || [])

const isAddEditDrawerVisible = ref(false)
const selectedGovernorate = ref<any>(null)

const openAddDrawer = () => {
  selectedGovernorate.value = null
  isAddEditDrawerVisible.value = true
}

const openEditDrawer = (governorate: any) => {
  selectedGovernorate.value = governorate
  isAddEditDrawerVisible.value = true
}

const deleteGovernorate = async (id: number) => {
  if (confirm('Are you sure you want to delete this governorate?')) {
    await $api(`/governorates/${id}`, { method: 'DELETE' })
    fetchGovernorates()
  }
}

const handleFormSubmit = () => {
  fetchGovernorates()
}
</script>

<template>
  <section>
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
          <div style="inline-size: 15.625rem;">
            <AppTextField
              v-model="searchQuery"
              placeholder="Search Area"
            />
          </div>

          <VBtn
            v-if="can('area.create' as any, 'all' as any)"
            prepend-icon="tabler-plus"
            @click="openAddDrawer"
          >
            Add Area
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
        :items="governorates"
        :headers="activeHeaders"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <!-- Area -->
        <template #item.name="{ item }: { item: any }">
          <span class="text-h6 font-weight-medium">{{ item.name }}</span>
        </template>

        <!-- Default Shipper -->
        <template #item.defaultShipper="{ item }: { item: any }">
          {{ item.defaultShipper?.name || '-' }}
        </template>

        <!-- Cities -->
        <template #item.cities="{ item }: { item: any }">
          <div class="d-flex flex-wrap gap-1 py-2" style="max-width: 300px;">
            <VChip
              v-for="city in item.cities"
              :key="city.id"
              size="small"
              variant="tonal"
            >
              {{ city.name }}
            </VChip>
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }: { item: any }">
          <div class="d-flex align-center gap-2">
            <IconBtn 
              v-if="can('area.update' as any, 'all' as any)"
              size="small" 
              @click="openEditDrawer(item)"
            >
              <VIcon icon="tabler-edit" />
              <VTooltip activator="parent">Edit Area</VTooltip>
            </IconBtn>
            <IconBtn 
              v-if="can('area.delete' as any, 'all' as any)"
              size="small" 
              @click="deleteGovernorate(item.id)"
            >
              <VIcon icon="tabler-trash" />
              <VTooltip activator="parent">Delete Area</VTooltip>
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <AddEditGovernorateDrawer
      v-model:isDrawerOpen="isAddEditDrawerVisible"
      :governorate="selectedGovernorate"
      @submit="handleFormSubmit"
    />
  </section>
</template>
