<script setup lang="ts">
import { useAbility } from '@/plugins/casl/composables/useAbility'
import { avatarText } from '@core/utils/formatters'
import girlUsingMobile from '@images/pages/girl-using-mobile.png'

const { can } = useAbility()

const resolveUserRoleVariant = (role: string) => {
  const roleLowerCase = role.toLowerCase()
  if (roleLowerCase === 'client') return { color: 'error', icon: 'tabler-user-heart' }
  if (roleLowerCase === 'shipper') return { color: 'success', icon: 'tabler-truck' }
  if (roleLowerCase === 'admin' || roleLowerCase === 'super-admin') return { color: 'primary', icon: 'tabler-crown' }
  return { color: 'info', icon: 'tabler-user' }
}

interface Permission {
  name: string
  read: boolean
  write: boolean
  create: boolean
}

interface RoleDetails {
  id?: number
  name: string
  permissions: string[]
}

interface ApiRole {
  id: number
  name: string
  label?: string
  users_count: number
  permissions: any[]
  users?: any[]
}

// 👉 Roles List
const roles = ref<ApiRole[]>([])
const isLoading = ref(true)

const fetchRoles = async () => {
  isLoading.value = true
  try {
    const res = await $api('/roles')
    
    // Support various formats: { data: [...] }, { data: { data: [...] } }, or [...]
    roles.value = res.data?.data || res.data || (Array.isArray(res) ? res : [])
    
  } catch (e) {
    roles.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchRoles)

const isRoleDialogVisible = ref(false)
const isAddRoleDialogVisible = ref(false)
const roleDetail = ref<RoleDetails>()

const editPermission = (item: ApiRole) => {
  roleDetail.value = {
    id: item.id,
    name: item.name,
    permissions: item.permissions ? item.permissions.map(p => p.name) : [],
  }
  isRoleDialogVisible.value = true
}

watch([isRoleDialogVisible, isAddRoleDialogVisible], ([roleDialog, addDialog]) => {
  if (!roleDialog && !addDialog) {
    fetchRoles()
  }
})

const deleteRole = async (id: number) => {
  if (confirm('Are you sure you want to delete this role?')) {
    try {
      await $api(`/roles/${id}`, { method: 'DELETE' })
      alert('Role deleted successfully!')
      fetchRoles()
    } catch (e: any) {
      const errorMsg = e.response?._data?.message || e.message || 'Failed to delete role'
      alert(errorMsg)
    }
  }
}
</script>

<template>
  <VRow v-if="!isLoading">
    <!-- 👉 Roles -->
    <VCol
      v-for="item in roles"
      :key="item.id"
      cols="12"
      sm="6"
      lg="4"
    >
      <VCard>
        <VCardText class="d-flex align-center pb-4">
          <div class="text-body-1">
            Total {{ item.users_count || 0 }} users
          </div>

          <VSpacer />

          <div class="v-avatar-group">
            <template
              v-for="user in item.users?.slice(0, 4)"
              :key="user.id"
            >
              <VAvatar
                size="40"
                :image="user.avatar || undefined"
                :color="!user.avatar ? resolveUserRoleVariant(item.name).color : undefined"
                :variant="!user.avatar ? 'tonal' : undefined"
              >
                <span v-if="!user.avatar">{{ avatarText(user.name) }}</span>
              </VAvatar>
            </template>
            <VAvatar
              v-if="item.users_count > 4"
              :color="$vuetify.theme.current.dark ? '#373B50' : '#EEEDF0'"
            >
              <span>
                +{{ item.users_count - 4 }}
              </span>
            </VAvatar>
          </div>
        </VCardText>

        <VCardText>
          <div class="d-flex justify-space-between align-center">
            <div>
              <h5 class="text-h5">
                {{ item.label || item.name }}
              </h5>
              <div v-if="can('role.update' as any, 'all' as any)" class="d-flex align-center">
                <a
                  href="javascript:void(0)"
                  @click="editPermission(item)"
                >
                  Edit Role
                </a>
              </div>
            </div>
            <div class="d-flex align-center gap-2">
        
              <IconBtn v-if="item.users_count === 0 && can('role.delete' as any, 'all' as any)" @click="deleteRole(item.id)">
                <VIcon
                  icon="tabler-trash"
                  class="text-error"
                />
              </IconBtn>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- 👉 Add New Role -->
    <VCol
      v-if="can('role.create' as any, 'all' as any)"
      cols="12"
      sm="6"
      lg="4"
    >
      <VCard
        class="h-100"
        :ripple="false"
      >
        <VRow
          no-gutters
          class="h-100"
        >
          <VCol
            cols="5"
            class="d-flex flex-column justify-end align-center mt-5"
          >
            <VImg
              width="85"
              :src="girlUsingMobile"
            />
          </VCol>

          <VCol cols="7">
            <VCardText class="d-flex flex-column align-end justify-end gap-4">
              <VBtn
                size="small"
                @click="isAddRoleDialogVisible = true"
              >
                Add New Role
              </VBtn>
              <div class="text-end">
                Add new role,<br> if it doesn't exist.
              </div>
            </VCardText>
          </VCol>
        </VRow>
      </VCard>
      <AddEditRoleDialog v-model:is-dialog-visible="isAddRoleDialogVisible" />
    </VCol>
  </VRow>
  <VRow v-else>
    <VCol cols="12" class="text-center">
        <VProgressCircular indeterminate color="primary" />
        <div class="mt-2 text-body-1">Loading Roles...</div>
    </VCol>
  </VRow>

  <AddEditRoleDialog
    v-model:is-dialog-visible="isRoleDialogVisible"
    v-model:role-permissions="roleDetail"
  />
</template>
