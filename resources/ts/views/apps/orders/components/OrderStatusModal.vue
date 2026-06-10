<script setup lang="ts">
import { useApi } from '@/composables/useApi';

interface Props {
  isDialogVisible: boolean
  order: any
  metadata?: any
}

const props = defineProps<Props>()
const emit = defineEmits(['update:isDialogVisible', 'statusUpdated'])

const statusData = ref({
  status: '',
  refused_reason_ids: [] as number[], // يسمح بالتكرار
  refused_reason_id_to_add: null as number | null,
  customReason: '',
  total_amount: 0,
  has_return: false,
})

const reasonsLoading = ref(false)
const reasonsData = ref<any>(null)
const allReasons = computed(() => Array.isArray(reasonsData.value) ? reasonsData.value : (reasonsData.value?.data || []))

const fetchReasons = async () => {
  if (props.metadata?.refused_reasons) {
    reasonsData.value = props.metadata.refused_reasons
    return
  }
  reasonsLoading.value = true
  const { data } = await useApi<any>('/refused-reasons').get().json()
  reasonsData.value = data.value
  reasonsLoading.value = false
}

watch(() => props.isDialogVisible, (newVal) => {
  if (newVal) {
    fetchReasons()
    if (props.order) {
      statusData.value = {
        status: props.order.status,
        refused_reason_ids: props.order.refused_reasons?.map((r: any) => r.id) || [],
        refused_reason_id_to_add: null,
        customReason: '', 
        total_amount: props.order.total_amount,
        has_return: props.order.has_return || false,
      }
    }
  }
})

const filteredReasons = computed(() => {
  return allReasons.value.filter((r: any) => r.is_active && r.status === statusData.value.status)
})


const showEditAmount = computed(() => {
  return statusData.value.refused_reason_ids
    .map(id => allReasons.value.find((r: any) => r.id === id))
    .some((r: any) => r && r.is_edit_amount)
})

watch(() => props.order, (newVal) => {
  if (newVal) {
    statusData.value = {
      status: newVal.status,
      refused_reason_ids: newVal.refused_reasons?.map((r: any) => r.id) || [],
      refused_reason_id_to_add: null,
      customReason: '',
      total_amount: newVal.total_amount,
      has_return: newVal.has_return || false,
    }
  }
}, { immediate: true })

const addRefusedReason = () => {
  if (statusData.value.refused_reason_id_to_add) {
    statusData.value.refused_reason_ids.push(statusData.value.refused_reason_id_to_add)
    statusData.value.refused_reason_id_to_add = null
  }
}

const onSubmit = async () => {
  if (!props.order?.id) return

  const payload: any = {
    status: statusData.value.status,
    refused_reason_ids: statusData.value.refused_reason_ids,
    reason: statusData.value.customReason,
  }

  if (showEditAmount.value) {
    payload.total_amount = statusData.value.total_amount
  }

  if (statusData.value.status === 'DELIVERED') {
    payload.has_return = statusData.value.has_return
  }

  const { error } = await useApi(`/orders/${props.order.id}/change-status`).patch(payload).json()

  if (!error.value) {
    emit('statusUpdated')
    emit('update:isDialogVisible', false)
  }
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    max-width="600"
    @update:model-value="val => emit('update:isDialogVisible', val)"
  >
    <VCard title="Update Order Status">
      <VCardText v-if="props.order">
        <VRow>
          <VCol cols="12">
            <AppSelect
              v-model="statusData.status"
              label="Select Status"
              :items="[
                { title: 'Out for delivery', value: 'OUT_FOR_DELIVERY' },
                { title: 'Delivered', value: 'DELIVERED' },
                { title: 'On hold', value: 'HOLD' },
                { title: 'Undelivered', value: 'UNDELIVERED' },
              ]"
              @update:model-value="statusData.refused_reason_ids = []"
            />
          </VCol>

          <!-- Reasons Tags (تكرار مسموح + حذف أي سبب) -->
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
                @click="addRefusedReason"
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

          <!-- Custom Reason -->
          <VCol cols="12">
            <AppTextField
              v-model="statusData.customReason"
              label="Custom Reason / Note"
              placeholder="Enter additional details..."
            />
          </VCol>

          <!-- Edit Amount if allowed -->
          <VCol cols="12" v-if="showEditAmount">
            <AppTextField
              v-model="statusData.total_amount"
              label="Update Total Amount"
              type="number"
            />
          </VCol>

          <!-- Has Return Toggle for Delivered -->
          <VCol cols="12" v-if="statusData.status === 'DELIVERED'">
            <div class="d-flex align-center gap-2">
              <VSwitch
                v-model="statusData.has_return"
                label="Has Return Package"
              />
              <VIcon icon="tabler-arrow-back-up" color="warning" v-if="statusData.has_return" />
            </div>
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
          @click="onSubmit"
        >
          Update Status
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
