<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import { useNotificationStore } from '@/stores/useNotificationStore'
import EditUserDrawer from '@/views/apps/user/list/EditUserDrawer.vue'
import { createUrl } from '@core/composable/createUrl'
import { avatarText } from '@core/utils/formatters'

const { success, error } = useNotificationStore()

// Headers for Clients
const headers = [
  { title: 'Client', key: 'user' },
  { title: 'Phone', key: 'phone' },
  { title: 'Address', key: 'address' },
  { title: 'Plan', key: 'plan' },
  { title: 'Content', key: 'content' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false },
]

// 👉 Store
const searchQuery = ref('')
const itemsPerPage = ref(10)
const page = ref(1)

const { can } = useAbility()

const STORAGE_KEY = 'clients-visible-columns'

// Define permission mapping for columns
const columnPermissions: Record<string, string> = {
  user: 'user.column.user.view',
  phone: 'user.column.phone.view',
  address: 'user.column.address.view',
  plan: 'user.column.plan.view',
  content: 'user.column.content.view',
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

// 👉 Fetching clients
const { data: clientsData, execute: fetchClients } = await useApi<any>(createUrl('/users', {
  query: {
    q: searchQuery,
    role: 'client',
    itemsPerPage,
    page,
  },
}))

const clients = computed(() => clientsData.value?.data || [])
const totalClients = computed(() => clientsData.value?.total || 0)

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
  fetchClients()
  success('Client updated successfully!')
}

const resolveUserStatusVariant = (blocked: boolean) => {
  return blocked ? 'error' : 'success'
}

const deleteClient = async (id: number) => {
  if (confirm('Are you sure you want to delete this client?')) {
    await $api(`/users/${id}`, { method: 'DELETE' })
    fetchClients()
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
            placeholder="Search Client"
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
        :items="clients"
        :items-length="totalClients"
        :headers="activeHeaders"
        class="text-no-wrap"
      >
        <!-- User -->
        <template #item.user="{ item }: { item: any }">
          <div class="d-flex align-center gap-x-3">
            <VAvatar
              size="34"
              color="primary"
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

        <!-- Address -->
        <template #item.address="{ item }: { item: any }">
          <span class="text-body-1">{{ item.client?.address || 'N/A' }}</span>
        </template>

        <!-- Plan -->
        <template #item.plan="{ item }: { item: any }">
          <VChip v-if="item.client?.plan" size="small" color="info" label>
            {{ item.client.plan.name }}
          </VChip>
          <span v-else>-</span>
        </template>

        <!-- Content -->
        <template #item.content="{ item }: { item: any }">
          <span class="text-body-1">{{ item.client?.shipping_content?.name || '-' }}</span>
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
              <VTooltip activator="parent">Edit Client</VTooltip>
            </IconBtn>
            <IconBtn 
              v-if="can('user.delete' as any, 'all' as any)"
              size="small" 
              @click="deleteClient(item.id)"
            >
              <VIcon icon="tabler-trash" />
              <VTooltip activator="parent">Delete Client</VTooltip>
            </IconBtn>
          </div>
        </template>

        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="totalClients"
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
