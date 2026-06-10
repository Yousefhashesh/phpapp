<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import AddEditPlanDrawer from '@/views/apps/plan/AddEditPlanDrawer.vue'
import { createUrl } from '@core/composable/createUrl'

// Headers
const headers = [
  { title: 'Plan Name', key: 'name' },
  { title: 'Order Count Cap', key: 'order_count' },
  { title: 'Pricing Summary', key: 'prices' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const searchQuery = ref('')
const { can } = useAbility()

const STORAGE_KEY = 'plans-visible-columns'

// Define permission mapping for columns
const columnPermissions: Record<string, string> = {
  name: 'plan.column.name.view',
  order_count: 'plan.column.order_count.view',
  prices: 'plan.column.prices.view',
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

// 👉 Fetching Plans
const { data: plansData, execute: fetchPlans } = await useApi<any>(createUrl('/plans', {
  query: {
    q: searchQuery,
  },
}))

const plans = computed(() => plansData.value || [])

const isAddEditDrawerVisible = ref(false)
const selectedPlan = ref<any>(null)

const openAddDrawer = () => {
  selectedPlan.value = null
  isAddEditDrawerVisible.value = true
}

const openEditDrawer = (plan: any) => {
  selectedPlan.value = plan
  isAddEditDrawerVisible.value = true
}

const deletePlan = async (id: number) => {
  if (confirm('Are you sure you want to delete this plan?')) {
    await $api(`/plans/${id}`, { method: 'DELETE' })
    fetchPlans()
  }
}

const handleFormSubmit = () => {
  fetchPlans()
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
              placeholder="Search Plan"
            />
          </div>

          <VBtn
            v-if="can('plan.create' as any, 'all' as any)"
            prepend-icon="tabler-plus"
            @click="openAddDrawer"
          >
            Add Plan
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
        :items="plans"
        :headers="activeHeaders"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <!-- Plan Name -->
        <template #item.name="{ item }: { item: any }">
          <span class="text-h6 font-weight-medium text-primary">{{ item.name }}</span>
        </template>

        <!-- Order Count -->
        <template #item.order_count="{ item }: { item: any }">
          <VChip size="small" variant="tonal" color="info">
            {{ item.order_count }} Orders
          </VChip>
        </template>

        <!-- Pricing Summary -->
        <template #item.prices="{ item }: { item: any }">
          <div class="d-flex flex-wrap gap-1 py-2">
            <template v-if="item.prices && item.prices.length > 0">
              <VTooltip
                v-for="p in item.prices"
                :key="p.id"
                location="top"
              >
                <template #activator="{ props }">
                  <VChip
                    v-bind="props"
                    size="small"
                    variant="tonal"
                  >
                    {{ p.governorate?.name }}: {{ p.price }} EGP
                  </VChip>
                </template>
                <span>{{ p.governorate?.name }} Cost</span>
              </VTooltip>
            </template>
            <span v-else class="text-muted text-sm">-</span>
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }: { item: any }">
          <div class="d-flex align-center gap-2">
            <IconBtn v-if="can('plan.update' as any, 'all' as any)" size="small" @click="openEditDrawer(item)">
              <VIcon icon="tabler-edit" />
              <VTooltip activator="parent">Edit Plan</VTooltip>
            </IconBtn>
            <IconBtn v-if="can('plan.delete' as any, 'all' as any)" size="small" @click="deletePlan(item.id)">
              <VIcon icon="tabler-trash" />
              <VTooltip activator="parent">Delete Plan</VTooltip>
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <AddEditPlanDrawer
      v-model:isDrawerOpen="isAddEditDrawerVisible"
      :plan="selectedPlan"
      @submit="handleFormSubmit"
    />
  </section>
</template>
