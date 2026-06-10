<script setup lang="ts">
import { useApi } from '@/composables/useApi';
import { useNotificationStore } from '@/stores/useNotificationStore';


interface Props {
  isDialogVisible: boolean
  selectedOrders: any[]
  metadata?: any
}

const props = defineProps<Props>()
const emit = defineEmits(['update:isDialogVisible', 'statusUpdated'])

const statusData = ref({
  status: 'DELIVERED',
  reason: '',
  refused_reason_ids: [] as number[], // يسمح بالتكرار
  refused_reason_id_to_add: null as number | null,
  total_amount: null as number | null,
})

const isLoading = ref(false)
const reasonsLoading = ref(false)
const reasonsData = ref<any>(null)
const notificationStatus = useNotificationStore()
const notify = (msg: string, color: string = 'success') => {
  notificationStatus.notify(msg, color)
}
const allReasons = computed(() => Array.isArray(reasonsData.value) ? reasonsData.value : (reasonsData.value?.data || []))

const fetchReasons = async () => {
  if (props.metadata?.refused_reasons) {
    reasonsData.value = props.metadata.refused_reasons
    return
  }
  reasonsLoading.value = true
  try {
    const { data } = await useApi<any>('/refused-reasons').get().json()
    reasonsData.value = data.value
  } catch (e) { console.error(e) }
  reasonsLoading.value = false
}

watch(() => props.isDialogVisible, (newVal) => {
  if (newVal) {
    fetchReasons()
  }
})

const filteredReasons = computed(() => {
  return allReasons.value.filter((r: any) => r.is_active && r.status === statusData.value.status)
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
  const payload: any = {
    order_ids: validOrders.map(o => o.id),
    status: statusData.value.status,
    reason: statusData.value.reason,
    refused_reason_ids: statusData.value.refused_reason_ids,
  }
  
  // Only include total_amount if it has a value
  if (statusData.value.total_amount !== null && statusData.value.total_amount !== '') {
    payload.total_amount = statusData.value.total_amount
  }

  try {
    const { error } = await useApi('/orders/bulk-change-status').patch(payload).json()
    if (!error.value) {
      emit('statusUpdated')
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
    max-width="500"
    @update:model-value="val => emit('update:isDialogVisible', val)"
  >
    <VCard :title="`Bulk Update Status (${props.selectedOrders.length} items)`" :loading="isLoading">
      <VCardText>
        <VRow>
          <VCol cols="12">
            <AppSelect
              v-model="statusData.status"
              label="New Status"
              :items="[
                { title: 'Out for delivery', value: 'OUT_FOR_DELIVERY' },
                { title: 'Delivered', value: 'DELIVERED' },
                { title: 'On hold', value: 'HOLD' },
                { title: 'Undelivered', value: 'UNDELIVERED' },
              ]"
            />
          </VCol>

          <VCol cols="12" v-if="filteredReasons.length">
            <div class="d-flex align-center gap-2">
              <AppSelect
                v-model="statusData.refused_reason_id_to_add"
                label="اختر سبب (يمكن التكرار)"
                :items="filteredReasons"
                item-title="reason"
                item-value="id"
                clearable
                style="flex: 1;"
              />
              <VBtn
                color="primary"
                variant="tonal"
                size="small"
                :disabled="!statusData.refused_reason_id_to_add"
                @click="() => {
                  if (statusData.refused_reason_id_to_add) {
                    statusData.refused_reason_ids.push(statusData.refused_reason_id_to_add)
                    statusData.refused_reason_id_to_add = null
                  }
                }"
              >
                إضافة
              </VBtn>
            </div>
            <div v-if="statusData.refused_reason_ids.length" class="mt-2">
              <span class="text-caption">الأسباب المختارة (يمكن التكرار):</span>
              <VChip
                v-for="(rid, idx) in statusData.refused_reason_ids"
                :key="idx + '-' + rid"
                class="ma-1"
                color="info"
                size="x-small"
                closable
                @click:close="statusData.refused_reason_ids.splice(idx, 1)"
              >
                {{ filteredReasons.find((r: any) => r.id === rid)?.reason || rid }}
              </VChip>
            </div>
          </VCol>

          <VCol cols="12">
            <AppTextField
              v-model="statusData.reason"
              label="Note / Custom Reason"
              placeholder="Enter details..."
            />
          </VCol>
          
          <VCol cols="12" v-if="statusData.refused_reason_id_to_add && filteredReasons.find((r: any) => r.id === statusData.refused_reason_id_to_add)?.is_edit_amount">
            <AppTextField
              v-model="statusData.total_amount"
              label="Force Total Amount (Optional)"
              type="number"
              hint="Only use if you want to override all selected orders amounts"
              persistent-hint
            />
          </VCol>
        </VRow>
      </VCardText>

      <VCardActions class="pb-6 px-6">
        <VSpacer />
        <VBtn color="secondary" variant="tonal" @click="emit('update:isDialogVisible', false)">Cancel</VBtn>
        <VBtn variant="elevated" color="primary" @click="onSubmit" :loading="isLoading">Apply Bulk Status</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
