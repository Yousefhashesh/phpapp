<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import { useNotificationStore } from '@/stores/useNotificationStore'
import EditUserDrawer from '@/views/apps/user/list/EditUserDrawer.vue'
import { createUrl } from '@core/composable/createUrl'
import { avatarText } from '@core/utils/formatters'

const { success } = useNotificationStore()

// Headers for Shippers
const headers = [
  { title: 'Shipper', key: 'user' },
  { title: 'Phone', key: 'phone' },
  { title: 'Commission', key: 'commission_rate' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false },
]

// 👉 Store
const searchQuery = ref('')
const itemsPerPage = ref(10)
const page = ref(1)

const { can } = useAbility()

const STORAGE_KEY = 'shippers-visible-columns'

// Define permission mapping for columns
const columnPermissions: Record<string, string> = {
  user: 'user.column.user.view',
  phone: 'user.column.phone.view',
  commission_rate: 'user.column.commission_rate.view',
  status: 'user.column.status.view',
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

// 👉 Fetching shippers
const { data: shippersData, execute: fetchShippers } = await useApi<any>(createUrl('/users', {
  query: {
    q: searchQuery,
    role: 'shipper',
    itemsPerPage, 
    page,
  },
}))

const shippers = computed(() => shippersData.value?.data || [])
const totalShippers = computed(() => shippersData.value?.total || 0)

const isEditUserDrawerVisible = ref(false)
const selectedUserForEdit = ref<any>(null)

const openEditUserDrawer = (user: any) => {
  selectedUserForEdit.value = user
  isEditUserDrawerVisible.value = true
}

const updateUser = async (userData: any) => {
  await $api(`/users/${userData.id}`, {
    method: 'PATCH',
    body: userData,
  })
  fetchShippers()
  success('Shipper updated successfully!')
}

const resolveUserStatusVariant = (blocked: boolean) => {
  return blocked ? 'error' : 'success'
}

const deleteShipper = async (id: number) => {
  if (confirm('Are you sure you want to delete this shipper?')) {
    await $api(`/users/${id}`, { method: 'DELETE' })
    fetchShippers()
  }
}
</script>

<template>
  <section>
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <div class="d-flex align-center flex-wrap gap-4">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search Shipper"
            style="inline-size: 15rem;"
          />

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

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items="shippers"
        :items-length="totalShippers"
        :headers="activeHeaders"
        class="text-no-wrap"
      >
        <!-- User -->
        <template #item.user="{ item }: { item: any }">
          <div class="d-flex align-center gap-x-3">
            <VAvatar
              size="34"
              color="success"
              variant="tonal"
            >
              <VImg v-if="item.avatar" :src="item.avatar" />
              <span v-else>{{ avatarText(item.name) }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">
                <RouterLink :to="{ name: 'apps-user-view-id', params: { id: item.id } }" class="font-weight-medium text-link">
                  {{ item.name }}
                </RouterLink>
              </h6>
              <span class="text-sm">@{{ item.username }}</span>
            </div>
          </div>
        </template>

        <!-- Commission Rate -->
        <template #item.commission_rate="{ item }: { item: any }">
          <span class="text-body-1">{{ item.shipper?.commission_rate || '0.00' }} EGP </span>
        </template>

        <!-- Status -->
        <template #item.status="{ item }: { item: any }">
          <VChip :color="resolveUserStatusVariant(item.is_blocked)" size="small" label>
            {{ item.is_blocked ? 'Blocked' : 'Active' }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }: { item: any }">
          <div class="d-flex align-center gap-2">
            <IconBtn size="small" :to="{ name: 'apps-user-view-id', params: { id: item.id } }">
              <VIcon icon="tabler-eye" />
              <VTooltip activator="parent">View Details</VTooltip>
            </IconBtn>
            <IconBtn 
              v-if="can('user.update' as any, 'all' as any)"
              size="small" 
              @click="openEditUserDrawer(item)"
            >
              <VIcon icon="tabler-edit" />
              <VTooltip activator="parent">Edit Shipper</VTooltip>
            </IconBtn>
            <IconBtn 
              v-if="can('user.delete' as any, 'all' as any)"
              size="small" 
              @click="deleteShipper(item.id)"
            >
              <VIcon icon="tabler-trash" />
              <VTooltip activator="parent">Delete Shipper</VTooltip>
            </IconBtn>
          </div>
        </template>

        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="totalShippers"
          />
        </template>
      </VDataTableServer>
    </VCard>

    <EditUserDrawer
      v-model:is-drawer-open="isEditUserDrawerVisible"
      :user="selectedUserForEdit"
      @user-data="updateUser"
    />
  </section>
</template>
