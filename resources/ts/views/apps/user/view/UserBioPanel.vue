<script setup lang="ts">
import ClientPlanEditDialog from '@/components/dialogs/ClientPlanEditDialog.vue'
import ShipperCommissionEditDialog from '@/components/dialogs/ShipperCommissionEditDialog.vue'
import { avatarText } from '@core/utils/formatters'

// Avatars imports
import avatar1 from '@images/avatars/avatar1.svg?url'
import avatar2 from '@images/avatars/avatar2.svg?url'
import avatar3 from '@images/avatars/avatar3.svg?url'
import avatar4 from '@images/avatars/avatar4.svg?url'
import avatar5 from '@images/avatars/avatar5.svg?url'
import avatar6 from '@images/avatars/avatar6.svg?url'

const avatars = [avatar1, avatar2, avatar3, avatar4, avatar5, avatar6]

interface Props {
  userData: {
    id: number
    name: string
    username: string
    role: string
    roles?: any[]
    email: string
    status: string | boolean
    is_blocked: boolean
    avatar: string
    account_type: number
    phone: string
    created_at: string
    shipper?: {
      commission_rate: string
    }
    client?: {
      plan_id: number
      plan?: { name: string }
      address: string
    }
  }
}

const props = defineProps<Props>()
const emit = defineEmits(['update'])

const isUserInfoEditDialogVisible = ref(false)
const isChangePlanDialogVisible = ref(false)
const isUpdateCommissionDialogVisible = ref(false)


// 👉 Role variant resolver
const resolveUserRoleVariant = (role: string) => {
  const roleLower = role?.toLowerCase()
  if (roleLower === 'client') return { color: 'success', icon: 'tabler-user' }
  if (roleLower === 'shipper' || roleLower === 'delivery') return { color: 'info', icon: 'tabler-truck' }
  if (roleLower === 'admin' || roleLower === 'super-admin') return { color: 'error', icon: 'tabler-server-2' }
  return { color: 'primary', icon: 'tabler-user' }
}

const userRole = computed(() => {
  const role = props.userData.role || props.userData.roles?.[0]?.name || 'user'
  return String(role)
})

const selectAvatar = async (avatar: string | null) => {
  try {
    await $api(`/users/${props.userData.id}`, {
      method: 'PUT',
      body: { avatar },
    })
    
    // Update local cookie if it's the current user
    const currentUser = useCookie<any>('userData')
    if (currentUser.value && (currentUser.value.id === props.userData.id || currentUser.value.user_id === props.userData.id)) {
      currentUser.value.avatar = avatar
    }

    emit('update')
  } catch (e) {
    // 
  }
}

const resetAvatar = async () => {
  try {
    await $api(`/users/${props.userData.id}`, {
      method: 'PUT',
      body: { avatar: null },
    })

    // Update local cookie if it's the current user
    const currentUser = useCookie<any>('userData')
    if (currentUser.value && (currentUser.value.id === props.userData.id || currentUser.value.user_id === props.userData.id)) {
      currentUser.value.avatar = null
    }

    emit('update')
  } catch (e) {
    // 
  }
}

const onUserInfoUpdate = async (updatedData: any) => {
  try {
    const res = await $api(`/users/${props.userData.id}`, {
      method: 'PUT',
      body: updatedData,
    })

    // Update local cookie if it's the current user
    const currentUser = useCookie<any>('userData')
    if (currentUser.value && (currentUser.value.id === props.userData.id || currentUser.value.user_id === props.userData.id)) {
      Object.assign(currentUser.value, updatedData)
    }

    emit('update')
    isUserInfoEditDialogVisible.value = false
    isChangePlanDialogVisible.value = false
    isUpdateCommissionDialogVisible.value = false
  } catch (e) {
    // 
  }
}

const suspendUser = async () => {
  try {
    await $api(`/users/${props.userData.id}/toggle-block`, {
      method: 'PATCH',
    })
    emit('update')
  } catch (e) {
    console.error(e)
  }
}
</script>

<template>
  <VRow>
    <!-- SECTION User Details -->
    <VCol cols="12">
      <VCard v-if="props.userData">
        <VCardText class="text-center pt-12">
          <!-- 👉 Avatar -->
          <div class="position-relative d-inline-block">
            <VAvatar
              rounded
              :size="120"
              :color="!props.userData.avatar ? 'primary' : undefined"
              :variant="!props.userData.avatar ? 'tonal' : undefined"
            >
              <VImg
                v-if="props.userData.avatar"
                :src="props.userData.avatar"
              />
              <span
                v-else
                class="text-5xl font-weight-medium"
              >
                {{ avatarText(props.userData.name) }}
              </span>
            </VAvatar>
            
            <VBtn
              icon="tabler-camera"
              variant="elevated"
              color="primary"
              size="x-small"
              class="position-absolute"
              style="inset-block-end: -10px; inset-inline-end: -10px;"
            >
              <VIcon icon="tabler-camera" />
              <VMenu activator="parent" location="bottom end" :close-on-content-click="true">
                <VCard width="200" class="pa-2">
                  <div class="d-flex flex-wrap gap-2 justify-center">
                    <VAvatar
                      v-for="img in avatars"
                      :key="img"
                      size="40"
                      class="cursor-pointer border-sm"
                      @click="selectAvatar(img)"
                    >
                      <VImg :src="img" />
                    </VAvatar>
                    <VAvatar
                      size="40"
                      color="secondary"
                      variant="tonal"
                      class="cursor-pointer border-sm"
                      @click="selectAvatar(null)"
                    >
                      <VIcon icon="tabler-trash" size="18" />
                      <VTooltip activator="parent">Reset to Letters</VTooltip>
                    </VAvatar>
                  </div>
                </VCard>
              </VMenu>
            </VBtn>
          </div>

          <!-- 👉 User name -->
          <h5 class="text-h5 mt-4">
            {{ props.userData.name }}
          </h5>

          <div class="d-flex align-center justify-center gap-2 mt-2">
            <!-- 👉 Role chip -->
            <VChip
              label
              :color="resolveUserRoleVariant(userRole).color"
              size="small"
              class="text-capitalize"
            >
              {{ userRole }}
            </VChip>

            <!-- Status chip -->
            <VChip
              label
              :color="props.userData.is_blocked ? 'error' : 'success'"
              size="small"
            >
              {{ props.userData.is_blocked ? 'Blocked' : 'Active' }}
            </VChip>
          </div>
        </VCardText>

        <VCardText>
          <!-- 👉 Details -->
          <h5 class="text-h5">
            Details
          </h5>

          <VDivider class="my-4" />

          <!-- 👉 User Details list -->
          <VList class="card-list mt-2">
            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Username:
                  <div class="d-inline-block text-body-1">
                    {{ props.userData.username }}
                  </div>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Phone:
                  <div class="d-inline-block text-body-1">
                    {{ props.userData.phone || 'N/A' }}
                  </div>
                </h6>
              </VListItemTitle>
            </VListItem>

            <!-- Project Specific Fields -->
            <template v-if="props.userData.account_type === 1">
              <VListItem>
                <VListItemTitle>
                  <h6 class="text-h6">
                    Shipping Plan:
                    <div class="d-inline-block text-body-1 text-primary">
                      {{ props.userData.client?.plan?.name || 'No Plan' }}
                    </div>
                  </h6>
                </VListItemTitle>
              </VListItem>
              <VListItem>
                <VListItemTitle>
                  <h6 class="text-h6">
                    Address:
                    <div class="d-inline-block text-body-1">
                      {{ props.userData.client?.address || 'N/A' }}
                    </div>
                  </h6>
                </VListItemTitle>
              </VListItem>
            </template>

            <template v-else-if="props.userData.account_type === 2">
              <VListItem>
                <VListItemTitle>
                  <h6 class="text-h6">
                    Commission Rate:
                    <div class="d-inline-block text-body-1 text-info">
                      {{ props.userData.shipper?.commission_rate }} EGP
                    </div>
                  </h6>
                </VListItemTitle>
              </VListItem>
            </template>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Created At:
                  <div class="d-inline-block text-body-1">
                    {{ new Date(props.userData.created_at).toLocaleDateString() }}
                  </div>
                </h6>
              </VListItemTitle>
            </VListItem>
          </VList>
        </VCardText>

        <!-- 👉 Edit and Suspend button -->
        <VCardText class="d-flex justify-center gap-x-4">
          <VBtn
            variant="elevated"
            @click="isUserInfoEditDialogVisible = true"
          >
            Edit Profile
          </VBtn>

          <VBtn
            variant="tonal"
            :color="props.userData.is_blocked ? 'success' : 'error'"
            @click="suspendUser"
          >
            {{ props.userData.is_blocked ? 'Unblock User' : 'Block User' }}
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>
    <!-- !SECTION -->

    <!-- SECTION Current Plan (If Client) -->
    <VCol v-if="props.userData.account_type === 1" cols="12">
      <VCard color="primary" variant="tonal">
        <VCardText>
          <div class="d-flex justify-space-between align-center">
            <div>
              <h6 class="text-h6 mb-1">Current Active Plan</h6>
              <h4 class="text-h4 text-primary">{{ props.userData.client?.plan?.name || 'Standard Plan' }}</h4>
            </div>
            <VAvatar size="48" color="primary" variant="elevated">
              <VIcon icon="tabler-premium-rights" size="28" />
            </VAvatar>
          </div>
          
          <VBtn
            block
            variant="elevated"
            color="primary"
            class="mt-6"
            @click="isChangePlanDialogVisible = true"
          >
            Change Shipping Plan
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>

    <!-- SECTION Shipper Metrics (If Shipper) -->
    <VCol v-if="props.userData.account_type === 2" cols="12">
      <VCard color="info" variant="tonal">
        <VCardText>
          <div class="d-flex justify-space-between align-center">
            <div>
              <h6 class="text-h6 mb-1">Commission Per Order</h6>
              <h4 class="text-h4 text-info">{{ props.userData.shipper?.commission_rate }} EGP</h4>
            </div>
            <VAvatar size="48" color="info" variant="elevated">
              <VIcon icon="tabler-currency-dollar" size="28" />
            </VAvatar>
          </div>
          
          <VBtn
            block
            variant="elevated"
            color="info"
            class="mt-6"
            @click="isUpdateCommissionDialogVisible = true"
          >
            Update Commission
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- 👉 Edit user info dialog -->
  <UserInfoEditDialog
    v-model:is-dialog-visible="isUserInfoEditDialogVisible"
    :user-data="{
      id: props.userData.id,
      name: props.userData.name,
      username: props.userData.username,
      phone: props.userData.phone,
      account_type: props.userData.account_type,
      commission_rate: props.userData.shipper?.commission_rate,
      plan_id: props.userData.client?.plan_id,
      address: props.userData.client?.address,
      is_blocked: props.userData.is_blocked
    }"
    @submit="onUserInfoUpdate"
  />

  <ClientPlanEditDialog
    v-model:is-dialog-visible="isChangePlanDialogVisible"
    :plan-id="props.userData.client?.plan_id"
    :address="props.userData.client?.address"
    @submit="onUserInfoUpdate"
  />

  <ShipperCommissionEditDialog
    v-model:is-dialog-visible="isUpdateCommissionDialogVisible"
    :commission-rate="props.userData.shipper?.commission_rate"
    @submit="onUserInfoUpdate"
  />
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 0.5rem;
}

.text-capitalize {
  text-transform: capitalize !important;
}
</style>
