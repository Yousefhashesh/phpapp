<script setup lang="ts">
import { useApi } from '@/composables/useApi';

interface Props {
  isDialogVisible: boolean
  order: any
  shippers: any[]
}

const props = defineProps<Props>()
const emit = defineEmits(['update:isDialogVisible', 'shipperUpdated'])

const loading = ref(false)
const errorMessages = ref<string[]>([])

const formData = ref({
  shipper_user_id: null as number | null,
  shipper_date: '',
  commission_amount: 0,
})

watch(() => props.order, (newVal) => {
  if (newVal) {
    formData.value = {
      shipper_user_id: newVal.shipper_user_id,
      shipper_date: newVal.shipper_date || new Date().toISOString().substr(0, 10),
      commission_amount: Number(newVal.commission_amount || 0),
    }
    errorMessages.value = []
  }
}, { immediate: true })

// When shipper changes, fetch their default commission
watch(() => formData.value.shipper_user_id, (newShipperId) => {
  if (newShipperId && newShipperId !== props.order?.shipper_user_id) {
    const selectedShipper = props.shippers.find(s => s.user_id === newShipperId)
    if (selectedShipper) {
       formData.value.commission_amount = Number(selectedShipper.commission_rate || 0)
    }
  }
})

const onSubmit = async () => {
  if (!props.order?.id) return
  
  loading.value = true
  errorMessages.value = []
  
  try {
    const shipperId = formData.value.shipper_user_id && typeof formData.value.shipper_user_id === 'object' 
      ? (formData.value.shipper_user_id as any).id || (formData.value.shipper_user_id as any).user_id
      : formData.value.shipper_user_id

    const fetchObj = useApi<any>(`/orders/${props.order.id}/change-shipper`).patch({
      shipper_user_id: shipperId ? Number(shipperId) : null,
      shipper_date: formData.value.shipper_date || null,
      commission_amount: formData.value.commission_amount,
    }).json()

    const { data, error } = await fetchObj

    if (error.value || !data.value) {
       // On 422/4xx, data.value usually contains the JSON response if already parsed
       const responseBody = data.value
       
       if (responseBody?.errors) {
          errorMessages.value = Object.values(responseBody.errors).flat() as string[]
       } else if (responseBody?.message) {
          errorMessages.value = [responseBody.message]
       } else {
          errorMessages.value = ['Update failed: Validation error or missing information.']
       }
    } else {
      emit('shipperUpdated')
      emit('update:isDialogVisible', false)
    }
  } catch (e: any) {
    errorMessages.value = ['A critical error occurred. Please try again.']
  }
  loading.value = false
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    max-width="500"
    @update:model-value="val => emit('update:isDialogVisible', val)"
  >
    <VCard :title="`Update Shipper - #${props.order?.code}`">
      <VCardText>
        <VAlert
          v-if="errorMessages.length"
          type="error"
          variant="tonal"
          closable
          class="mb-4"
          @click:close="errorMessages = []"
        >
          <ul class="ms-4 mb-0">
            <li v-for="msg in errorMessages" :key="msg">{{ msg }}</li>
          </ul>
        </VAlert>

        <VRow>
          <VCol cols="12">
            <AppSelect
              v-model="formData.shipper_user_id"
              label="Select Shipper"
              placeholder="Choose a shipper"
              :items="props.shippers"
              item-title="name"
              item-value="id"
              clearable
            />
          </VCol>
          
          <VCol cols="12">
            <AppTextField
              v-model="formData.shipper_date"
              label="Assignment Date"
              type="date"
            />
          </VCol>

          <VCol cols="12">
            <AppTextField
              v-model="formData.commission_amount"
              label="Shipper Commission"
              type="number"
              step="0.01"
              prefix="EGP"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VCardActions class="pb-6 px-6">
        <VSpacer />
        <VBtn
          color="secondary"
          variant="tonal"
          @click="emit('update:isDialogVisible', false)"
        >
          Cancel
        </VBtn>
        <VBtn
          variant="elevated"
          color="primary"
          :loading="loading"
          @click="onSubmit"
        >
          Update Shipper
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
