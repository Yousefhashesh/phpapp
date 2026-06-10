<script setup lang="ts">
import { useApi } from '@/composables/useApi';
import { useNotificationStore } from '@/stores/useNotificationStore';

interface Props {
  isDialogVisible: boolean
  selectedOrders: any[]
  shippers: any[]
}

const props = defineProps<Props>()
const emit = defineEmits(['update:isDialogVisible', 'shipperUpdated'])

const selectedShipperId = ref<number | null>(null)
const commissionAmount = ref<number | null>(null)
const shipperDate = ref(new Date().toISOString().substr(0, 10))
const isLoading = ref(false)
const notificationStatus = useNotificationStore()
const notify = (msg: string, color: string = 'success') => {
  notificationStatus.notify(msg, color)
}

watch(selectedShipperId, (newId) => {
  if (newId) {
    const shipper = props.shippers.find(s => s.id === newId)
    if (shipper) {
      commissionAmount.value = shipper.commission_rate
    }
  } else {
    commissionAmount.value = null
  }
})

const onSubmit = async () => {
  const validOrders = props.selectedOrders.filter(o => {
    const isLocked = o.is_shipper_collected || o.is_client_settled || o.is_shipper_returned || o.is_client_returned
    const isFinal = ['DELIVERED', 'UNDELIVERED', 'CANCELLED'].includes(o.status)
    return !isLocked && !isFinal
  })
  
  if (!validOrders.length) {
    notify('لا يوجد أوردرات قابلة للتعديل (تم استثناء الأوردرات المسلمة أو المرتجعة أو التي تم تحصيلها بالفعل)', 'warning')
    return
  }

  isLoading.value = true
  const shipperId = selectedShipperId.value && typeof selectedShipperId.value === 'object' 
    ? (selectedShipperId.value as any).id || (selectedShipperId.value as any).user_id
    : selectedShipperId.value

  const payload = {
    order_ids: validOrders.map(o => o.id),
    shipper_user_id: shipperId ? Number(shipperId) : null,
    shipper_date: shipperDate.value || null,
    commission_amount: commissionAmount.value,
  }

  try {
    const { error } = await useApi('/orders/bulk-change-shipper').patch(payload).json()
    if (!error.value) {
      emit('shipperUpdated')
      emit('update:isDialogVisible', false)
    } else {
      notify('خطأ أثناء التحديث: ' + (error.value?.message || 'يرجى المحاولة مرة أخرى'), 'error')
    }
  } catch (e) {
    console.error(e)
    notify('حدث خطأ غير متوقع', 'error')
  }
  isLoading.value = false
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    max-width="400"
    @update:model-value="val => emit('update:isDialogVisible', val)"
  >
    <VCard :title="`Bulk Assign Shipper (${props.selectedOrders.length} items)`" :loading="isLoading">
      <VCardText>
        <VRow>
          <VCol cols="12">
            <AppAutocomplete
              v-model="selectedShipperId"
              label="Select Shipper"
              :items="props.shippers"
              item-title="name"
              item-value="id"
              placeholder="Select or search shipper..."
              required
              clearable
            />
          </VCol>
          <VCol cols="12" md="6">
            <AppTextField
              v-model="commissionAmount"
              label="Commission Amount"
              placeholder="Rate per order"
              type="number"
            />
          </VCol>
          <VCol cols="12" md="6">
             <AppDateTimePicker
              v-model="shipperDate"
              label="Assignment Date"
            />
          </VCol>
          <VCol cols="12">
            <p class="text-xs text-secondary italic">Assigns the selected shipper and commission to all checked orders. Existing amounts will be recalculated based on this commission.</p>
          </VCol>
        </VRow>
      </VCardText>

      <VCardActions class="pb-6 px-6">
        <VSpacer />
        <VBtn color="secondary" variant="tonal" @click="emit('update:isDialogVisible', false)">Cancel</VBtn>
        <VBtn variant="elevated" color="primary" @click="onSubmit" :loading="isLoading">Assign Shipper</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
