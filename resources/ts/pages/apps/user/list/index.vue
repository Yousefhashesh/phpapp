<script setup lang="ts">
import AddNewUserDrawer from '@/views/apps/user/list/AddNewUserDrawer.vue'
import EditUserDrawer from '@/views/apps/user/list/EditUserDrawer.vue'
import type { UserProperties } from '@/views/apps/user/types'
import { createUrl } from '@core/composable/createUrl'
import { avatarText } from '@core/utils/formatters'

// 👉 Store
const searchQuery = ref('')
const selectedRole = ref()
const selectedStatus = ref()

definePage({
  meta: {
    action: 'manage',
    subject: 'user.page',
  },
})

// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const selectedRows = ref([])

// Update data table options
const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

// Headers
const headers = [
  { title: 'User', key: 'user' },
  { title: 'Role', key: 'role' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false },
]

// 👉 Fetching users
const { data: usersData, execute: fetchUsers } = await useApi<any>(createUrl('/users', {
  query: {
    q: searchQuery,
    status: selectedStatus,
    role: selectedRole,
    itemsPerPage,
    page,
    sortBy,
    orderBy,
  },
}))

const users = computed((): UserProperties[] => usersData.value?.data || [])
const totalUsers = computed(() => usersData.value?.total || 0)

// 👉 stats from API
const widgetData = computed(() => {
  const stats = usersData.value?.stats || { total: 0, shippers: 0, clients: 0, blocked: 0 }
  return [
    { title: 'Total Users', value: stats.total, desc: 'Real-time Analytics', icon: 'tabler-users', iconColor: 'primary' },
    { title: 'Shippers', value: stats.shippers, desc: 'Active Shippers', icon: 'tabler-truck', iconColor: 'success' },
    { title: 'Clients', value: stats.clients, desc: 'Active Clients', icon: 'tabler-user-heart', iconColor: 'error' },
    { title: 'Blocked', value: stats.blocked, desc: 'Accounts Locked', icon: 'tabler-user-off', iconColor: 'warning' },
  ]
})

// 👉 search filters
const roles = [
  { title: 'Admin', value: 'admin' },
  { title: 'Client', value: 'client' },
  { title: 'Shipper', value: 'shipper' },
]

const status = [
  { title: 'Active', value: 'active' },
  { title: 'Blocked', value: 'blocked' },
]

const resolveUserRoleVariant = (role: string) => {
  const roleLowerCase = role.toLowerCase()
  if (roleLowerCase === 'client') return { color: 'error', icon: 'tabler-user-heart' }
  if (roleLowerCase === 'shipper') return { color: 'success', icon: 'tabler-truck' }
  if (roleLowerCase === 'admin') return { color: 'primary', icon: 'tabler-crown' }
  return { color: 'secondary', icon: 'tabler-user' }
}

const resolveUserStatusVariant = (blocked: boolean) => {
  return blocked ? 'error' : 'success'
}

const isAddNewUserDrawerVisible = ref(false)
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
  fetchUsers()
  alert('User updated successfully!')
}

const addNewUser = async (userData: UserProperties) => {
  await $api('/users', {
    method: 'POST',
    body: userData,
  })
  fetchUsers()
}

const deleteUser = async (id: number) => {
  await $api(`/users/${id}`, {
    method: 'DELETE',
  })
  fetchUsers()
}
</script>

<template>
  <section>
    <!-- 👉 Widgets -->
    <div class="d-flex mb-6">
      <VRow>
        <template
          v-for="(data, id) in widgetData"
          :key="id"
        >
          <VCol
            cols="12"
            md="3"
            sm="6"
          >
            <VCard>
              <VCardText>
                <div class="d-flex justify-space-between">
                  <div class="d-flex flex-column gap-y-1">
                    <div class="text-body-1 text-high-emphasis">
                      {{ data.title }}
                    </div>
                    <div class="d-flex gap-x-2 align-center">
                      <h4 class="text-h4">
                        {{ data.value }}
                      </h4>
                    </div>
                    <div class="text-sm">
                      {{ data.desc }}
                    </div>
                  </div>
                  <VAvatar
                    :color="data.iconColor"
                    variant="tonal"
                    rounded
                    size="42"
                  >
                    <VIcon
                      :icon="data.icon"
                      size="26"
                    />
                  </VAvatar>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </template>
      </VRow>
    </div>

    <VCard class="mb-6">
      <VCardItem class="pb-4">
        <VCardTitle>Filters</VCardTitle>
      </VCardItem>

      <VCardText>
        <VRow>
          <VCol
            cols="12"
            sm="6"
          >
            <AppSelect
              v-model="selectedRole"
              placeholder="Select Role"
              :items="roles"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          <VCol
            cols="12"
            sm="6"
          >
            <AppSelect
              v-model="selectedStatus"
              placeholder="Select Status"
              :items="status"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VCardText class="d-flex flex-wrap gap-4">
        <div class="me-3 d-flex gap-3">
          <AppSelect
            :model-value="itemsPerPage"
            :items="[
              { value: 10, title: '10' },
              { value: 25, title: '25' },
              { value: 50, title: '50' },
              { value: 100, title: '100' },
              { value: -1, title: 'All' },
            ]"
            style="inline-size: 6.25rem;"
            @update:model-value="itemsPerPage = parseInt($event, 10)"
          />
        </div>
        <VSpacer />

        <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
          <div style="inline-size: 15.625rem;">
            <AppTextField
              v-model="searchQuery"
              placeholder="Search User"
            />
          </div>

          <VBtn
            prepend-icon="tabler-plus"
            @click="isAddNewUserDrawerVisible = true"
          >
            Add New User
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:model-value="selectedRows"
        v-model:page="page"
        :items="users"
        item-value="id"
        :items-length="totalUsers"
        :headers="headers"
        class="text-no-wrap"
     
        @update:options="updateOptions"
      >
        <!-- User -->
        <template #item.user="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar
              size="34"
              :color="!item.avatar ? resolveUserRoleVariant(item.roles?.[0]?.name || '').color : undefined"
              :variant="!item.avatar ? 'tonal' : undefined"
            >
              <VImg
                v-if="item.avatar"
                :src="item.avatar"
              />
              <span v-else>{{ avatarText(item.name) }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">
                <RouterLink
                  :to="{ name: 'apps-user-view-id', params: { id: item.id } }"
                  class="font-weight-medium text-link"
                >
                  {{ item.name }}
                </RouterLink>
              </h6>
              <div class="text-sm">
                @{{ item.username }}
              </div>
            </div>
          </div>
        </template>

        <!-- 👉 Role -->
        <template #item.role="{ item }">
          <div class="d-flex align-center gap-x-2">
            <template v-for="role in item.roles" :key="role.name">
               <VChip
                size="small"
                :color="resolveUserRoleVariant(role.name).color"
                label
                class="text-capitalize"
              >
                {{ role.label || role.name }}
              </VChip>
            </template>
          </div>
        </template>

        <!-- Status -->
        <template #item.status="{ item }">
          <VChip
            :color="resolveUserStatusVariant(item.is_blocked)"
            size="small"
            label
            class="text-capitalize"
          >
            {{ item.is_blocked ? 'Blocked' : 'Active' }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex align-center gap-2">
            <IconBtn size="small" :to="{ name: 'apps-user-view-id', params: { id: item.id } }">
              <VIcon icon="tabler-eye" />
              <VTooltip activator="parent">View Details</VTooltip>
            </IconBtn>
            <IconBtn size="small" @click="openEditUserDrawer(item)">
              <VIcon icon="tabler-edit" />
              <VTooltip activator="parent">Edit User</VTooltip>
            </IconBtn>
            <IconBtn size="small" @click="deleteUser(item.id)">
              <VIcon icon="tabler-trash" />
              <VTooltip activator="parent">Delete User</VTooltip>
            </IconBtn>
          </div>
        </template>

        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="totalUsers"
          />
        </template>
      </VDataTableServer>
    </VCard>
    
    <AddNewUserDrawer
      v-model:is-drawer-open="isAddNewUserDrawerVisible"
      @user-data="addNewUser"
    />

    <EditUserDrawer
      v-model:is-drawer-open="isEditUserDrawerVisible"
      :user="selectedUserForEdit"
      @user-data="updateUser"
    />
  </section>
</template>
