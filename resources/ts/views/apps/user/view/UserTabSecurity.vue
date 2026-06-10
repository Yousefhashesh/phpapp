<script setup lang="ts">
import { computed, ref } from 'vue'

interface Props {
  userData: {
    id: number
    login_sessions: {
      id: number
      device_type: string
      browser_name: string
      ip_address: string
      location: string
      last_activity: string
      is_active: boolean
      is_current: boolean
      logout_at: string | null
    }[]
  }
}

const props = defineProps<Props>()

const isNewPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const isTwoFactorDialogOpen = ref(false)
const smsVerificationNumber = ref('+1(968) 819-2547')

const passwordData = ref({
  password: '',
  confirmPassword: '',
})

const isSubmitting = ref(false)
const alertMessage = ref('')
const alertType = ref<'success' | 'error' | 'warning'>('warning')

const onSubmit = async () => {
  if (passwordData.value.password !== passwordData.value.confirmPassword) {
    alertMessage.value = 'Passwords do not match'
    alertType.value = 'error'
    return
  }

  isSubmitting.value = true
  try {
    // @ts-ignore
    await $api(`/users/${props.userData.id}`, {
      method: 'PUT',
      body: { password: passwordData.value.password },
    })
    alertMessage.value = 'Password updated successfully'
    alertType.value = 'success'
    passwordData.value = { password: '', confirmPassword: '' }
  } catch (e: any) {
    alertMessage.value = e.response?._data?.message || 'Failed to update password'
    alertType.value = 'error'
  } finally {
    isSubmitting.value = false
  }
}

// Recent devices Headers
const recentDeviceHeader = [
  { title: 'BROWSER', key: 'browser' },
  { title: 'DEVICE', key: 'device' },
  { title: 'LOCATION', key: 'location' },
  { title: 'RECENT ACTIVITY', key: 'activity' },
  { title: 'ACTION', key: 'action', sortable: false },
]

const resolveDeviceIcon = (platform: string) => {
  const p = platform?.toLowerCase() || ''
  if (p.includes('windows')) return { icon: 'tabler-brand-windows', color: 'info' }
  if (p.includes('android')) return { icon: 'tabler-brand-android', color: 'success' }
  if (p.includes('apple') || p.includes('ios') || p.includes('mac')) return { icon: 'tabler-brand-apple', color: 'secondary' }
  return { icon: 'tabler-device-laptop', color: 'primary' }
}

const emit = defineEmits(['update'])

const logoutSession = async (sessionId: number) => {
  try {
    await $api(`/logout-session/${sessionId}`, { method: 'DELETE' })
    emit('update')
  } catch (e) {
    console.error(e)
  }
}

const recentDevices = computed(() => {
  const sessions = props.userData.login_sessions || []
  
  // Only show active sessions and deduplicate by IP + Device
  const uniqueSessions: any[] = []
  const seen = new Set()

  sessions.forEach(session => {
    // Skip if logged out
    if (session.logout_at && !session.is_active) return

    const key = `${session.ip_address}-${session.device_type}-${session.browser_name}`
    if (!seen.has(key)) {
      seen.add(key)
      const { icon, color } = resolveDeviceIcon(session.device_type || 'Windows')
      uniqueSessions.push({
        id: session.id,
        browser: `${session.browser_name || 'Unknown'} on ${session.device_type || 'Desktop'}`,
        icon,
        color,
        device: session.device_type,
        location: session.location || 'Unknown',
        activity: session.last_activity || 'N/A',
        ip: session.ip_address,
        is_current: session.is_current
      })
    }
  })

  return uniqueSessions
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <!-- 👉 Change password -->
      <VCard title="Change Password">
        <VCardText>
          <VAlert
            v-if="alertMessage"
            closable
            variant="tonal"
            :type="alertType"
            class="mb-4"
            :text="alertMessage"
          />
          
          <VAlert
            v-else
            closable
            variant="tonal"
            color="warning"
            class="mb-4"
            title="Ensure that these requirements are met"
            text="Minimum 8 characters long"
          />

          <VForm @submit.prevent="onSubmit">
            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="passwordData.password"
                  label="New Password"
                  placeholder="············"
                  :type="isNewPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isNewPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="passwordData.confirmPassword"
                  label="Confirm Password"
                  autocomplete="confirm-password"
                  placeholder="············"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                />
              </VCol>

              <VCol cols="12">
                <VBtn
                  type="submit"
                  :loading="isSubmitting"
                >
                  Change Password
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>


    <VCol cols="12">
      <!-- 👉 Recent devices -->

      <VCard title="Recent devices">
        <VDivider />
        <VDataTable
          :items="recentDevices"
          :headers="recentDeviceHeader"
          hide-default-footer
          class="text-no-wrap"
        >
          <template #item.browser="{ item }">
            <div class="d-flex align-center gap-x-4">
              <VIcon
                :icon="item.icon"
                :color="item.color"
                :size="22"
              />
              <div class="d-flex flex-column">
                <div class="text-body-1 text-high-emphasis">
                  {{ item.browser }}
                </div>
                <div class="text-xs text-disabled">
                  IP: {{ item.ip }}
                </div>
              </div>
              <VChip
                v-if="item.is_current"
                color="success"
                size="x-small"
                label
                class="ms-2"
              >
                Current
              </VChip>
            </div>
          </template>

          <template #item.action="{ item }">
            <VBtn
              v-if="!item.is_current"
              icon="tabler-logout"
              variant="text"
              color="error"
              size="small"
              @click="logoutSession(item.id)"
            >
              <VIcon icon="tabler-logout" />
              <VTooltip activator="parent">Log out device</VTooltip>
            </VBtn>
          </template>

          <!-- TODO Refactor this after vuetify provides proper solution for removing default footer -->
          <template #bottom />
        </VDataTable>
      </VCard>
    </VCol>
  </VRow>

  <!-- 👉 Enable One Time Password Dialog -->
  <TwoFactorAuthDialog
    v-model:is-dialog-visible="isTwoFactorDialogOpen"
    :sms-code="smsVerificationNumber"
  />
</template>
