<script setup lang="ts">
import UpdateUserRoleDialog from '@/components/dialogs/UpdateUserRoleDialog.vue'
import AddNewUserDrawer from '@/views/apps/user/list/AddNewUserDrawer.vue'
import EditUserDrawer from '@/views/apps/user/list/EditUserDrawer.vue'
import type { UserProperties } from '@/views/apps/user/types'
import { createUrl } from '@core/composable/createUrl'
import { avatarText } from '@core/utils/formatters'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

// Headers
const headers = [
  { title: t('User'), key: 'user' },
  { title: t('Role'), key: 'role' },
  { title: t('Status'), key: 'status' },
  { title: t('Actions'), key: 'actions', sortable: false },
]

// 👉 Store
const searchQuery = ref('')
const selectedRole = ref()
const selectedStatus = ref()

const { can } = useAbility()

const STORAGE_KEY = 'roles-users-visible-columns'

// Define permission mapping for columns
const columnPermissions: Record<string, string> = {
  user: 'user.column.user.view',
  role: 'user.column.role.view',
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

// 👉 User update role logic
const isUpdateRoleDialogVisible = ref(false)
const selectedUserForRole = ref<any>({ id: 0, name: '', roles: [] })

const openUpdateRoleDialog = (user: any) => {
  selectedUserForRole.value = {
    id: user.id,
    name: user.name,
    roles: user.roles || [],
  }
  isUpdateRoleDialogVisible.value = true
}

const onRoleUpdateSuccess = () => {
  fetchUsers()
}

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

// 👉 Fetching real roles for filter
const { data: fetchedRoles } = await useApi<any>('/roles')
const roles = computed(() => {
  const rawRoles = fetchedRoles.value?.data || fetchedRoles.value || []
  return rawRoles.map((r: any) => ({ title: r.label || r.name, value: r.name }))
})

const users = computed((): UserProperties[] => usersData.value?.data || [])
const totalUsers = computed(() => usersData.value?.total || 0)

const route = useRoute()

onMounted(() => {
  // If role is passed in query (e.g. from nav menu), set the filter
  if (route.query.role) {
    selectedRole.value = route.query.role
  }
  
  fetchUsers()
})

// 👉 search filters
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
  if (confirm('Are you sure you want to delete this user?')) {
    await $api(`/users/${id}`, {
      method: 'DELETE',
    })
    fetchUsers()
  }
}
</script>

<template>
  <section>
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <div class="d-flex gap-2 align-center">
          <p class="text-body-1 mb-0">{{ t('Showing') }}</p>
          <AppSelect
            :model-value="itemsPerPage"
            :items="[
              { value: 10, title: '10' },
              { value: 25, title: '25' },
              { value: 50, title: '50' },
              { value: 100, title: '100' },
              { value: -1, title: t('All') },
            ]"
            style="inline-size: 5.5rem;"
            @update:model-value="itemsPerPage = parseInt($event, 10)"
          />
        </div>

        <VSpacer />

        <div class="d-flex align-center flex-wrap gap-4">
          <AppTextField
            v-model="searchQuery"
            :placeholder="t('Search User')"
            style="inline-size: 15.625rem;"
          />
          <AppSelect
            v-model="selectedRole"
            :placeholder="t('Select Role')"
            :items="roles"
            clearable
            clear-icon="tabler-x"
            style="inline-size: 10rem;"
          />
           <AppSelect
              v-model:model-value="selectedStatus"
              :placeholder="t('Select Status')"
              :items="status"
              clearable
              clear-icon="tabler-x"
              style="inline-size: 10rem;"
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
        v-model:model-value="selectedRows"
        v-model:page="page"
        :items="users"
        :items-length="totalUsers"
        :headers="activeHeaders"
        class="text-no-wrap"
        show-select
        @update:options="updateOptions"
      >
        <!-- User -->
        <template #item.user="{ item }: { item: any }">
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
        <template #item.role="{ item }: { item: any }">
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
        <template #item.status="{ item }: { item: any }">
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
        <template #item.actions="{ item }: { item: any }">
          <div class="d-flex align-center gap-2">
            <IconBtn
              v-if="can('user.update-role' as any, 'all' as any)"
              size="small"
              @click="openUpdateRoleDialog(item)"
            >
              <VIcon
                icon="tabler-shield-check"
                class="text-success"
              />
              <VTooltip activator="parent">Change Role</VTooltip>
            </IconBtn>
            
            <IconBtn
              v-if="can('user.update' as any, 'all' as any)"
              size="small"
              @click="openEditUserDrawer(item)"
            >
              <VIcon icon="tabler-edit" />
              <VTooltip activator="parent">Edit User</VTooltip>
            </IconBtn>

            <IconBtn
              size="small"
              :to="{ name: 'apps-user-view-id', params: { id: item.id } }"
            >
              <VIcon icon="tabler-eye" />
              <VTooltip activator="parent">View Details</VTooltip>
            </IconBtn>
            
            <IconBtn
              v-if="can('user.delete' as any, 'all' as any)"
              size="small"
              @click="deleteUser(item.id)"
            >
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

    <UpdateUserRoleDialog
      v-model:is-dialog-visible="isUpdateRoleDialogVisible"
      :user="selectedUserForRole"
      @success="onRoleUpdateSuccess"
    />
  </section>
</template>
