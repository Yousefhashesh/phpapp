<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import type { VForm } from 'vuetify/components/VForm'
import { requiredValidator } from '@/@core/utils/validators'

interface Props {
  isDrawerOpen: boolean
  visit?: any
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
const shipperId = ref<number | null>(null)
const clientId = ref<number | null>(null)
const pickupRequestId = ref<number | null>(null)
const materialRequestId = ref<number | null>(null)
const visitCost = ref(0)

// 👉 Fetching Data
const { data: shippersData } = await useApi<any>('/users?role=shipper')
const shippers = computed(() => shippersData.value?.data || [])

const { data: clientsData } = await useApi<any>('/users?role=client')
const clients = computed(() => clientsData.value?.data || [])

const { data: pickupsData } = await useApi<any>('/pickup-requests')
const pickups = computed(() => pickupsData.value?.data || [])

const { data: materialRequestsData } = await useApi<any>('/material-requests')
const materialRequests = computed(() => materialRequestsData.value?.data || [])

watch(() => props.isDrawerOpen, (isOpen) => {
  if (isOpen) {
    if (props.visit) {
      id.value = props.visit.id
      shipperId.value = props.visit.shipper_id
      clientId.value = props.visit.client_id
      pickupRequestId.value = props.visit.pickup_request_id
      materialRequestId.value = props.visit.material_request_id
      visitCost.value = props.visit.visit_cost || 0
    } else {
      id.value = null
      shipperId.value = null
      clientId.value = null
      pickupRequestId.value = null
      materialRequestId.value = null
      visitCost.value = 0
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
    shipper_id: shipperId.value,
    client_id: clientId.value,
    pickup_request_id: pickupRequestId.value,
    material_request_id: materialRequestId.value,
    visit_cost: visitCost.value,
  }

  const method = id.value ? 'PATCH' : 'POST'
  const url = id.value ? `/visits/${id.value}` : '/visits'

  try {
    await $api(url, {
      method,
      body: payload,
    })
    
    alert(`Visit ${id.value ? 'updated' : 'created'} successfully!`)
    emit('submit')
    closeNavigationDrawer()
  } catch (error) {
    console.error('Error saving visit:', error)
    alert('Error saving visit. Ensure client matches linked requests.')
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
      :title="id ? 'Update Visit' : 'Add Visit'"
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
                  v-model="shipperId"
                  label="Shipper"
                  placeholder="Select Shipper"
                  :items="shippers"
                  item-title="name"
                  item-value="id"
                  :rules="[requiredValidator]"
                />
              </VCol>

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
                  v-model="pickupRequestId"
                  label="Linked Pickup Request"
                  placeholder="None"
                  :items="pickups"
                  item-title="id"
                  item-value="id"
                  clearable
                >
                  <template #item="{ props, item }">
                    <VListItem v-bind="props" :subtitle="`Status: ${item.raw.status}`" />
                  </template>
                </AppSelect>
              </VCol>

              <VCol cols="12">
                <AppSelect
                  v-model="materialRequestId"
                  label="Linked Material Request"
                  placeholder="None"
                  :items="materialRequests"
                  item-title="id"
                  item-value="id"
                  clearable
                >
                   <template #item="{ props, item }">
                    <VListItem v-bind="props" :subtitle="`Status: ${item.raw.status}`" />
                  </template>
                </AppSelect>
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="visitCost"
                  type="number"
                  label="Visit Cost (EGP)"
                  hint="Automatically adjusted if combined."
                  persistent-hint
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
