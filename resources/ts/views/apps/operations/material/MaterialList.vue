<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import AddEditMaterialDrawer from '@/views/apps/operations/material/AddEditMaterialDrawer.vue'
import { createUrl } from '@core/composable/createUrl'

// Headers
const headers = [
  { title: 'Material', key: 'name' },
  { title: 'Code', key: 'code' },
  { title: 'Cost', key: 'cost_price' },
  { title: 'Sale', key: 'sale_price' },
  { title: 'Stock', key: 'stock' },
  { title: 'Status', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const searchQuery = ref('')

// 👉 Fetching Materials
const { data: materialsData, execute: fetchMaterials } = await useApi<any>(createUrl('/materials', {
  query: {
    q: searchQuery,
  },
}))

const materials = computed(() => materialsData.value || [])

const { can } = useAbility()

const STORAGE_KEY = 'materials-visible-columns'

// Define permission mapping for columns
const columnPermissions: Record<string, string> = {
  name: 'material.column.name.view',
  code: 'material.column.code.view',
  cost_price: 'material.column.cost_price.view',
  sale_price: 'material.column.sale_price.view',
  stock: 'material.column.stock.view',
  is_active: 'material.column.is_active.view',
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
const selectedMaterial = ref<any>(null)

const openAddDrawer = () => {
  selectedMaterial.value = null
  isAddEditDrawerVisible.value = true
}

const openEditDrawer = (material: any) => {
  selectedMaterial.value = material
  isAddEditDrawerVisible.value = true
}

const deleteMaterial = async (id: number) => {
  if (confirm('Are you sure you want to delete this material?')) {
    await $api(`/materials/${id}`, { method: 'DELETE' })
    fetchMaterials()
  }
}

const handleFormSubmit = () => {
  fetchMaterials()
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
              placeholder="Search Material"
            />
          </div>

          <VBtn
            v-if="can('material.create' as any, 'all' as any)"
            prepend-icon="tabler-plus"
            @click="openAddDrawer"
          >
            Add Material
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
        :items="materials"
        :headers="activeHeaders"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <!-- Material Name -->
        <template #item.name="{ item }: { item: any }">
          <div class="d-flex flex-column">
            <span class="text-h6 font-weight-medium">{{ item.name }}</span>
            <span v-if="item.notes" class="text-xs text-muted">{{ item.notes }}</span>
          </div>
        </template>

        <!-- Code -->
        <template #item.code="{ item }: { item: any }">
          <VChip size="small" variant="tonal">{{ item.code || '-' }}</VChip>
        </template>

        <!-- Pricing -->
        <template #item.cost_price="{ item }: { item: any }">
          <span class="text-sm">{{ item.cost_price }} EGP</span>
        </template>
        <template #item.sale_price="{ item }: { item: any }">
          <span class="text-sm font-weight-bold">{{ item.sale_price }} EGP</span>
        </template>

        <!-- Stock -->
        <template #item.stock="{ item }: { item: any }">
          <VChip 
            size="small" 
            :color="item.stock > 10 ? 'success' : (item.stock > 0 ? 'warning' : 'error')"
            variant="tonal"
          >
            {{ item.stock }}
          </VChip>
        </template>

        <!-- Status -->
        <template #item.is_active="{ item }: { item: any }">
          <VChip
            size="small"
            :color="item.is_active ? 'success' : 'secondary'"
            variant="tonal"
          >
            {{ item.is_active ? 'Active' : 'Inactive' }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }: { item: any }">
          <div class="d-flex align-center gap-2">
            <IconBtn v-if="can('material.update' as any, 'all' as any)" size="small" @click="openEditDrawer(item)">
              <VIcon icon="tabler-edit" />
              <VTooltip activator="parent">Edit Material</VTooltip>
            </IconBtn>
            <IconBtn v-if="can('material.delete' as any, 'all' as any)" size="small" @click="deleteMaterial(item.id)">
              <VIcon icon="tabler-trash" />
              <VTooltip activator="parent">Delete Material</VTooltip>
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <AddEditMaterialDrawer
      v-model:isDrawerOpen="isAddEditDrawerVisible"
      :material="selectedMaterial"
      @submit="handleFormSubmit"
    />
  </section>
</template>
