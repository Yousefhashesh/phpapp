<script setup lang="ts">
import { requiredValidator } from '@/@core/utils/validators'
import { useApi } from '@/composables/useApi'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import type { VForm } from 'vuetify/components/VForm'

interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'userData', value: any): void
}

interface Props {
  isDrawerOpen: boolean
  user?: any
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()

const isFormValid = ref(false)
const refForm = ref<VForm>()

// Form Fields
const id = ref<number | null>(null)
const name = ref('')
const username = ref('')
const phone = ref('')
const selectedRoles = ref<string[]>([])
const accountType = ref('0') 
const isBlocked = ref(false)
const commissionRate = ref<number>()
const address = ref('')
const planId = ref<number>()
const shippingContentId = ref<number>()
const canSettleBeforeShipperCollected = ref(false)

// 👉 Fetching lists
const { data: rolesData } = await useApi<any>('/roles')
const roles = computed(() => (rolesData.value?.data || rolesData.value || []).map((r: any) => ({ title: r.label || r.name, value: r.name })))

const { data: plansData } = await useApi<any>('/plans')
const plans = computed(() => (plansData.value?.data || plansData.value || []).map((p: any) => ({ title: p.name, value: p.id })))

const { data: contentsData } = await useApi<any>('/contents')
const contents = computed(() => (contentsData.value?.data || contentsData.value || []).map((c: any) => ({ title: c.name, value: c.id })))

// 👉 Watch props.user and update form
watch(() => props.isDrawerOpen, (isOpen) => {
  if (isOpen && props.user) {
    id.value = props.user.id
    name.value = props.user.name
    username.value = props.user.username
    phone.value = props.user.phone || ''
    selectedRoles.value = props.user.roles?.map((r: any) => r.name) || []
    isBlocked.value = !!props.user.is_blocked
    
    // Determine account type and specific data
    if (props.user.shipper) {
      accountType.value = '2'
      commissionRate.value = props.user.shipper.commission_rate
    } else if (props.user.client) {
      accountType.value = '1'
      address.value = props.user.client.address || ''
      planId.value = props.user.client.plan_id
      shippingContentId.value = props.user.client.shipping_content_id
      canSettleBeforeShipperCollected.value = !!props.user.client.can_settle_before_shipper_collected
    } else {
      accountType.value = '0'
    }
  }
})

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('userData', {
        id: id.value,
        name: name.value,
        username: username.value,
        phone: phone.value,
        roles: selectedRoles.value,
        account_type: accountType.value,
        is_blocked: isBlocked.value,
        commission_rate: commissionRate.value,
        address: address.value,
        plan_id: planId.value,
        shipping_content_id: shippingContentId.value,
        can_settle_before_shipper_collected: canSettleBeforeShipperCollected.value,
      })
      emit('update:isDrawerOpen', false)
    }
  })
}

const handleDrawerModelValueUpdate = (val: boolean) => {
  emit('update:isDrawerOpen', val)
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection
      title="Edit User"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol cols="12">
                <AppTextField v-model="name" :rules="[requiredValidator]" label="Name" />
              </VCol>

              <VCol cols="12">
                <AppTextField v-model="username" :rules="[requiredValidator]" label="Username" />
              </VCol>

              <VCol cols="12">
                <AppTextField v-model="phone" label="Phone" />
              </VCol>

              <VCol cols="12">
                <AppSelect
                  v-model="selectedRoles"
                  label="Roles"
                  :items="roles"
                  multiple
                  chips
                  closable-chips
                />
              </VCol>

              <VCol cols="12">
                <AppSelect
                  v-model="accountType"
                  label="Account Type"
                  :items="[
                    { title: 'Regular / Admin', value: '0' },
                    { title: 'Client', value: '1' },
                    { title: 'Shipper', value: '2' },
                  ]"
                />
              </VCol>

              <!-- Shipper Data -->
              <VCol v-if="accountType === '2'" cols="12">
                <AppTextField v-model="commissionRate" type="number" label="Commission Rate (%)" />
              </VCol>

              <!-- Client Data -->
              <template v-if="accountType === '1'">
                <VCol cols="12">
                  <AppTextField v-model="address" label="Address" />
                </VCol>
                <VCol cols="12">
                  <AppSelect v-model="planId" label="Plan" :items="plans" />
                </VCol>
                <VCol cols="12">
                  <AppSelect v-model="shippingContentId" label="Shipping Content" :items="contents" />
                </VCol>
                <VCol cols="12">
                  <VSwitch v-model="canSettleBeforeShipperCollected" label="Can Settle Before Shipper Collected" color="success" />
                </VCol>
              </template>

              <VCol cols="12">
                <VSwitch v-model="isBlocked" label="Blocked" color="error" />
              </VCol>

              <VCol cols="12">
                <VBtn type="submit" class="me-3">Save Changes</VBtn>
                <VBtn type="reset" variant="tonal" color="error" @click="closeNavigationDrawer">Cancel</VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
