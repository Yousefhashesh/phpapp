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
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()

const isFormValid = ref(false)
const refForm = ref<VForm>()
const name = ref('')
const username = ref('')
const password = ref('')
const phone = ref('')
const selectedRoles = ref<string[]>([])
const accountType = ref('0') 
const isBlocked = ref(false)
const commissionRate = ref<number>()
const address = ref('')
const planId = ref<number>()
const shippingContentId = ref<number>()
const canSettleBeforeShipperCollected = ref(false)

// 👉 Fetching roles for selection
const { data: rolesData } = await useApi<any>('/roles')
const roles = computed(() => {
  const rawRoles = rolesData.value?.data || rolesData.value || []
  return rawRoles.map((r: any) => ({ title: r.label || r.name, value: r.name }))
})

// 👉 Fetching plans for selection
const { data: plansData } = await useApi<any>('/plans')
const plans = computed(() => {
  const rawPlans = plansData.value?.data || plansData.value || []
  return rawPlans.map((p: any) => ({ title: p.name, value: p.id }))
})

// 👉 Fetching shipping contents for selection
const { data: contentsData } = await useApi<any>('/contents')
const contents = computed(() => {
  const rawContents = contentsData.value?.data || contentsData.value || []
  return rawContents.map((c: any) => ({ title: c.name, value: c.id }))
})

// 👉 drawer close
const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)

  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('userData', {
        name: name.value,
        username: username.value,
        password: password.value,
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
      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
      })
    }
  })
}

const handleDrawerModelValueUpdate = (val: boolean) => {
  emit('update:isDrawerOpen', val)
}
</script>

<template>
  <VNavigationDrawer
    data-allow-mismatch
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Title -->
    <AppDrawerHeaderSection
      title="Add New User"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <!-- 👉 Form -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- 👉 name -->
               <VCol cols="12">
                <AppTextField
                  v-model="name"
                  :rules="[requiredValidator]"
                  label="Name"
                  placeholder="John Doe"
                />
              </VCol>

              <!-- 👉 Username -->
              <VCol cols="12">
                <AppTextField
                  v-model="username"
                  :rules="[requiredValidator]"
                  label="Username"
                  placeholder="johndoe"
                />
              </VCol>

              <!-- 👉 Password -->
              <VCol cols="12">
                <AppTextField
                  v-model="password"
                  type="password"
                  :rules="[requiredValidator]"
                  label="Password"
                  placeholder="············"
                />
              </VCol>

              <!-- 👉 Phone -->
              <VCol cols="12">
                <AppTextField
                  v-model="phone"
                  label="Phone"
                  placeholder="0123456789"
                />
              </VCol>

              <!-- 👉 Role -->
              <VCol cols="12">
                <AppSelect
                  v-model="selectedRoles"
                  label="Select Roles"
                  placeholder="Select Roles"
                  :items="roles"
                  multiple
                  chips
                  closable-chips
                />
              </VCol>

              <!-- 👉 Account Type -->
              <VCol cols="12">
                <AppSelect
                  v-model="accountType"
                  label="Account Type"
                  placeholder="Select Account Type"
                  :items="[
                    { title: 'Regular / Admin', value: '0' },
                    { title: 'Client', value: '1' },
                    { title: 'Shipper', value: '2' },
                  ]"
                />
              </VCol>

              <!-- 👉 Commission Rate (Shipper) -->
              <VCol v-if="accountType === '2'" cols="12">
                <AppTextField
                  v-model="commissionRate"
                  type="number"
                  label="Commission Rate"
                  placeholder="10.00"
                />
              </VCol>

              <!-- 👉 Address (Client) -->
              <VCol v-if="accountType === '1'" cols="12">
                <AppTextField
                  v-model="address"
                  label="Address"
                  placeholder="Client Address"
                />
              </VCol>

              <!-- 👉 Plan (Client) -->
              <VCol v-if="accountType === '1'" cols="12">
                <AppSelect
                  v-model="planId"
                  label="Select Plan"
                  placeholder="Select Plan"
                  :items="plans"
                />
              </VCol>

              <!-- 👉 Shipping Content (Client) -->
              <VCol v-if="accountType === '1'" cols="12">
                <AppSelect
                  v-model="shippingContentId"
                  label="Shipping Content"
                  placeholder="Select Content"
                  :items="contents"
                />
              </VCol>
              <VCol v-if="accountType === '1'" cols="12">
                <VSwitch v-model="canSettleBeforeShipperCollected" label="Can Settle Before Shipper Collected" color="success" />
              </VCol>

              <!-- 👉 Is Blocked -->
              <VCol cols="12">
                <VSwitch
                  v-model="isBlocked"
                  label="Blocked"
                  color="error"
                />
              </VCol>

              <!-- 👉 Submit and Cancel -->
              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  Submit
                </VBtn>
                <VBtn
                  type="reset"
                  variant="tonal"
                  color="error"
                  @click="closeNavigationDrawer"
                >
                  Cancel
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
