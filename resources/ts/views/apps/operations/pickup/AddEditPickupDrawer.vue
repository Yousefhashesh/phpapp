<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import type { VForm } from 'vuetify/components/VForm'
import { requiredValidator } from '@/@core/utils/validators'

interface Props {
  isDrawerOpen: boolean
  pickup?: any
}

interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'submit'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()

const isFormValid = ref(false)
const refForm = ref<VForm>()

// Form data
const id = ref<number | null>(null)
const clientId = ref<number | null>(null)
const shipperId = ref<number | null>(null)
const pickupDate = ref('')
const combinedWithMaterial = ref(false)
const pickupCost = ref(0)
const status = ref('PENDING')
const approvalStatus = ref('PENDING')
const notes = ref('')

// 👉 Fetching Data for selects
const { data: clientsData } = await useApi<any>('/users?role=client')
const clients = computed(() => clientsData.value?.data || [])

const { data: shippersData } = await useApi<any>('/users?role=shipper')
const shippers = computed(() => shippersData.value?.data || [])

watch(() => props.isDrawerOpen, (isOpen) => {
  if (isOpen) {
    if (props.pickup) {
      id.value = props.pickup.id
      clientId.value = props.pickup.client_id
      shipperId.value = props.pickup.shipper_id
      pickupDate.value = props.pickup.pickup_date ? props.pickup.pickup_date.split(' ')[0] : ''
      combinedWithMaterial.value = !!props.pickup.combined_with_material
      pickupCost.value = props.pickup.pickup_cost || 0
      status.value = props.pickup.status || 'PENDING'
      approvalStatus.value = props.pickup.approval_status || 'PENDING'
      notes.value = props.pickup.notes || ''
    } else {
      id.value = null
      clientId.value = null
      shipperId.value = null
      pickupDate.value = ''
      combinedWithMaterial.value = false
      pickupCost.value = 0
      status.value = 'PENDING'
      approvalStatus.value = 'PENDING'
      notes.value = ''
    }
  }
})

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })
}

const onSubmit = async () => {
  const { valid } = await refForm.value!.validate()
  if (!valid) return

  const payload = {
    client_id: clientId.value,
    shipper_id: shipperId.value,
    pickup_date: pickupDate.value,
    combined_with_material: combinedWithMaterial.value,
    pickup_cost: pickupCost.value,
    status: status.value,
    approval_status: approvalStatus.value,
    notes: notes.value,
  }

  const method = id.value ? 'PATCH' : 'POST'
  const url = id.value ? `/pickup-requests/${id.value}` : '/pickup-requests'

  try {
    await $api(url, {
      method,
      body: payload,
    })
    
    alert(`Pickup request ${id.value ? 'updated' : 'created'} successfully!`)
    emit('submit')
    closeNavigationDrawer()
  } catch (error) {
    console.error('Error saving pickup request:', error)
    alert('Error saving pickup request.')
  }
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
    <!-- 👉 Header -->
    <AppDrawerHeaderSection
      :title="id ? 'Update Pickup Request' : 'Add Pickup Request'"
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
              <VCol cols="12">
                <AppSelect
                  v-model="clientId"
                  label="Client"
                  placeholder="Select Client"
                  :items="clients"
                  item-title="name"
                  item-value="id"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12">
                <AppSelect
                  v-model="shipperId"
                  label="Assigned Shipper"
                  placeholder="Select Shipper"
                  :items="shippers"
                  item-title="name"
                  item-value="id"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="pickupDate"
                  type="date"
                  label="Pickup Date"
                />
              </VCol>

              <VCol cols="12">
                <VSwitch
                  v-model="combinedWithMaterial"
                  label="Combined with Material Request"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="pickupCost"
                  type="number"
                  label="Pickup Cost (EGP)"
                />
              </VCol>

              <VCol cols="12">
                <AppSelect
                  v-model="status"
                  label="Execution Status"
                  :items="['PENDING', 'ASSIGNED', 'COMPLETED', 'CANCELLED']"
                />
              </VCol>

              <VCol cols="12">
                <AppSelect
                  v-model="approvalStatus"
                  label="Approval Status"
                  :items="['PENDING', 'APPROVED', 'REJECTED']"
                />
              </VCol>

              <VCol cols="12">
                <AppTextarea
                  v-model="notes"
                  label="Notes"
                  placeholder="Additional pickup info..."
                />
              </VCol>

              <VCol cols="12">
                <div class="d-flex gap-4">
                  <VBtn type="submit">
                    {{ id ? 'Update' : 'Submit' }}
                  </VBtn>
                  <VBtn
                    color="secondary"
                    variant="tonal"
                    @click="closeNavigationDrawer"
                  >
                    Cancel
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
