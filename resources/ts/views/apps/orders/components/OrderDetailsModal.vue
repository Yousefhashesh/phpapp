<script setup lang="ts">
import { useApi } from '@/composables/useApi';
import { createUrl } from '@core/composable/createUrl';
import { ref, watch } from 'vue';

interface Props {
  isDialogVisible: boolean
  order: any
}

const props = defineProps<Props>()
const emit = defineEmits(['update:isDialogVisible', 'print'])

const historyLogs = ref<any[]>([])
const isFetching = ref(false)
const historyPage = ref(1)
const historyPerPage = ref(10)
const historyTotal = ref(0)

const fetchHistory = async () => {
  if (!props.order?.id) return
  isFetching.value = true
  try {
    const { data } = await useApi<any>(createUrl(`/orders/${props.order.id}/history`, {
      query: {
        page: historyPage.value,
        per_page: historyPerPage.value,
      },
    })).get().json()

    const payload = data.value
    historyLogs.value = payload?.data || payload || []
    historyTotal.value = payload?.total ?? historyLogs.value.length
  } catch (e) {
    console.error(e)
  }
  isFetching.value = false
}

watch(() => props.isDialogVisible, val => {
  if (val) {
    historyPage.value = 1
    fetchHistory()
  }
})

watch([historyPage, historyPerPage], () => {
  if (props.isDialogVisible) fetchHistory()
})

const close = () => {
  emit('update:isDialogVisible', false)
}

const statusColors: Record<string, string> = {
  OUT_FOR_DELIVERY: 'primary',
  DELIVERED: 'success',
  HOLD: 'warning',
  UNDELIVERED: 'error',
  PENDING: 'secondary',
}
const resolveStatusColor = (status: string) => statusColors[status] || 'secondary'

const getLogIcon = (log: any) => {
  if (log.action === 'created') return 'tabler-circle-plus'
  if (log.new_values?.status) return 'tabler-circle-check'
  if (log.new_values?.is_shipper_collected) return 'tabler-cash'
  if (log.new_values?.is_shipper_returned) return 'tabler-arrow-back-up'
  if (log.new_values?.is_client_settled) return 'tabler-mood-dollar'
  if (log.new_values?.is_client_returned) return 'tabler-package-export'
  if (log.new_values?.shipper_user_id) return 'tabler-truck'
  return 'tabler-edit'
}

const getLogColor = (log: any) => {
  if (log.action === 'created') return 'success'
  if (log.new_values?.status) return resolveStatusColor(log.new_values.status)
  if (log.new_values?.is_shipper_collected) return 'success'
  if (log.new_values?.is_shipper_returned) return 'warning'
  if (log.new_values?.is_client_settled) return 'info'
  if (log.new_values?.is_client_returned) return 'error'
  if (log.new_values?.shipper_user_id) return 'info'
  return 'primary'
}

const getLogActionLabel = (log: any) => {
  if (log.action === 'created') return 'إنشاء الطلب'
  if (log.new_values?.status) return 'تغيير الحالة'
  if (log.new_values?.is_shipper_collected) return 'تحصيل المندوب'
  if (log.new_values?.is_shipper_returned) return 'مرتجع المندوب'
  if (log.new_values?.is_client_settled) return 'تسوية العميل'
  if (log.new_values?.is_client_returned) return 'مرتجع العميل'
  if (log.new_values?.shipper_user_id) return 'تعيين مندوب'
  return 'تحديث بيانات'
}

const getLogMessage = (log: any) => {
  if (log.action === 'created') return 'تم تسجيل الأوردر بنجاح'
  if (log.new_values?.status) {
    const oldStatus = log.old_values?.status
    const newStatus = log.new_values.status
    return oldStatus
      ? `تم تغيير الحالة من ${oldStatus} إلى ${newStatus}`
      : `تم تغيير الحالة إلى ${newStatus}`
  }
  if (log.new_values?.total_amount !== undefined) {
    return `تعديل المبلغ: ${log.old_values?.total_amount ?? '-'} ← ${log.new_values.total_amount} ج.م`
  }
  if (log.new_values?.latest_status_note) return `ملاحظة: ${log.new_values.latest_status_note}`
  if (log.new_values?.is_shipper_collected) return 'تم تأكيد تحصيل المبلغ من المندوب'
  if (log.new_values?.is_shipper_returned) return 'تم تنفيذ مرتجع المندوب للمخزن'
  if (log.new_values?.is_client_settled) return 'تمت تسوية المستحقات المالية مع العميل'
  if (log.new_values?.is_client_returned) return 'تم تسليم المرتجع للعميل رسمياً'
  if (log.new_values?.shipper_user_id) return 'تم تعيين مندوب جديد للتوصيل'
  if (log.message) return log.message
  if (log.label) return log.label
  return 'تم تحديث بيانات الأوردر'
}

const formatLogChanges = (log: any) => {
  const oldValues = log.old_values || {}
  const newValues = log.new_values || {}
  const keys = [...new Set([...Object.keys(oldValues), ...Object.keys(newValues)])]
  return keys
    .filter(key => !['updated_at', 'created_at'].includes(key))
    .map(key => `${key}: ${oldValues[key] ?? '—'} → ${newValues[key] ?? '—'}`)
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    width="960"
    scrollable
    @update:model-value="close"
  >
    <VCard v-if="props.order">
      <VCardTitle class="d-flex align-center justify-space-between pa-4 bg-light-primary">
        <div class="d-flex align-center gap-2 flex-wrap">
          <VIcon icon="tabler-package" color="primary" />
          <span class="text-h6 font-weight-bold">تفاصيل الشحنة #{{ props.order.code }}</span>
          <VChip :color="resolveStatusColor(props.order.status)" size="small">
            {{ props.order.status }}
          </VChip>
        </div>
        <VBtn icon variant="text" size="small" @click="close">
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>

      <VCardText class="pa-0">
        <div class="pa-6 bg-var-theme-background">
          <VRow>
            <VCol cols="12" md="4">
              <VCard variant="outlined" elevation="0" class="h-100">
                <VCardText>
                  <div class="d-flex align-center gap-2 mb-4 border-bottom pb-2">
                    <VIcon icon="tabler-user" color="info" />
                    <span class="font-weight-bold">بيانات المستلم</span>
                  </div>
                  <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">الاسم:</span>
                      <span class="font-weight-medium">{{ props.order.receiver_name }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">الموبايل:</span>
                      <span class="font-weight-medium">{{ props.order.phone }}</span>
                    </div>
                    <div v-if="props.order.phone_2" class="d-flex justify-space-between">
                      <span class="text-disabled">موبايل 2:</span>
                      <span class="font-weight-medium">{{ props.order.phone_2 }}</span>
                    </div>
                    <div class="d-flex flex-column gap-1">
                      <span class="text-disabled">العنوان:</span>
                      <span class="text-sm font-weight-medium bg-light-info pa-2 rounded mt-1">
                        {{ props.order.governorate?.name }} - {{ props.order.city?.name }}<br>
                        {{ props.order.address }}
                      </span>
                    </div>
                  </div>
                </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12" md="4">
              <VCard variant="outlined" elevation="0" class="h-100">
                <VCardText>
                  <div class="d-flex align-center gap-2 mb-4 border-bottom pb-2">
                    <VIcon icon="tabler-receipt-2" color="success" />
                    <span class="font-weight-bold">البيانات المالية</span>
                  </div>
                  <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">إجمالي الأوردر:</span>
                      <span class="font-weight-bold text-primary">{{ props.order.total_amount }} ج.م</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">مبلغ التحصيل (COD):</span>
                      <span class="font-weight-bold text-success">{{ props.order.cod_amount }} ج.م</span>
                    </div>
                    <VDivider />
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">سعر الشحن:</span>
                      <span>{{ props.order.shipping_fee }} ج.م</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">عمولة المندوب:</span>
                      <span>{{ props.order.commission_amount }} ج.م</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">صافي الشركة:</span>
                      <span>{{ props.order.company_amount }} ج.م</span>
                    </div>
                  </div>
                </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12" md="4">
              <VCard variant="outlined" elevation="0" class="h-100">
                <VCardText>
                  <div class="d-flex align-center gap-2 mb-4 border-bottom pb-2">
                    <VIcon icon="tabler-truck-delivery" color="warning" />
                    <span class="font-weight-bold">معلومات الشحن</span>
                  </div>
                  <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">العميل:</span>
                      <span class="font-weight-medium">{{ props.order.client?.name }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">المندوب:</span>
                      <span class="font-weight-medium">{{ props.order.shipper?.name || 'غير محدد' }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">المحتوى:</span>
                      <span>{{ props.order.shipping_content?.name || '-' }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">تحصيل مندوب:</span>
                      <VChip :color="props.order.is_shipper_collected ? 'success' : 'error'" size="x-small">
                        {{ props.order.is_shipper_collected ? 'نعم' : 'لا' }}
                      </VChip>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-disabled">تسوية عميل:</span>
                      <VChip :color="props.order.is_client_settled ? 'success' : 'error'" size="x-small">
                        {{ props.order.is_client_settled ? 'نعم' : 'لا' }}
                      </VChip>
                    </div>
                    <div>
                      <span class="text-xs text-disabled">تاريخ الإنشاء:</span>
                      <div class="text-xs">{{ new Date(props.order.created_at).toLocaleString('ar-EG') }}</div>
                    </div>
                  </div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>

          <VCard v-if="props.order.order_note || props.order.latest_status_note" variant="tonal" color="secondary" class="mt-4">
            <VCardText class="pa-3">
              <div v-if="props.order.order_note" class="mb-2">
                <span class="font-weight-bold text-xs">ملاحظة الأوردر:</span>
                <p class="mb-0 text-sm">{{ props.order.order_note }}</p>
              </div>
              <div v-if="props.order.latest_status_note">
                <span class="font-weight-bold text-xs">آخر ملاحظة حالة:</span>
                <p class="mb-0 text-sm text-info">{{ props.order.latest_status_note }}</p>
              </div>
            </VCardText>
          </VCard>
        </div>

        <VDivider />

        <div class="pa-6">
          <div class="text-subtitle-1 font-weight-bold mb-4 d-flex align-center gap-2">
            <VIcon icon="tabler-history" size="20" color="secondary" />
            سجل الأحداث على الأوردر
          </div>

          <div v-if="isFetching" class="text-center py-6">
            <VProgressCircular indeterminate color="primary" />
          </div>
          <div v-else-if="!historyLogs.length" class="text-center text-disabled py-6">
            لا توجد سجلات حالياً
          </div>
          <VTimeline v-else side="end" density="compact" truncate-line="both">
            <VTimelineItem
              v-for="log in historyLogs"
              :key="log.id"
              :dot-color="getLogColor(log)"
              size="small"
              fill-dot
            >
              <template #icon>
                <VIcon :icon="getLogIcon(log)" size="14" color="white" />
              </template>
              <VCard variant="outlined" class="mb-2">
                <VCardText class="pa-3">
                  <div class="d-flex justify-space-between align-start flex-wrap gap-2 mb-1">
                    <span class="font-weight-bold text-sm">{{ getLogActionLabel(log) }}</span>
                    <span class="text-xs text-disabled">
                      {{ new Date(log.created_at).toLocaleString('ar-EG') }}
                    </span>
                  </div>
                  <p class="text-sm mb-1">{{ getLogMessage(log) }}</p>
                  <p v-if="log.user?.name" class="text-xs text-disabled mb-1">
                    بواسطة: {{ log.user.name }}
                  </p>
                  <div v-if="formatLogChanges(log).length" class="mt-2">
                    <VChip
                      v-for="(change, idx) in formatLogChanges(log)"
                      :key="idx"
                      size="x-small"
                      variant="tonal"
                      class="me-1 mb-1"
                    >
                      {{ change }}
                    </VChip>
                  </div>
                </VCardText>
              </VCard>
            </VTimelineItem>
          </VTimeline>

          <div v-if="historyTotal > historyPerPage" class="d-flex align-center justify-space-between flex-wrap gap-3 mt-4">
            <div class="d-flex align-center gap-2">
              <span class="text-xs text-disabled">عرض</span>
              <VSelect
                v-model="historyPerPage"
                :items="[10, 25, 50]"
                density="compact"
                variant="outlined"
                hide-details
                style="max-inline-size: 80px;"
              />
              <span class="text-xs text-disabled">من {{ historyTotal }}</span>
            </div>
            <VPagination
              v-model="historyPage"
              :length="Math.ceil(historyTotal / historyPerPage)"
              density="comfortable"
              :total-visible="5"
            />
          </div>
        </div>
      </VCardText>

      <VCardText class="d-flex justify-end gap-2 pa-4 bg-light-primary">
        <VBtn variant="tonal" color="secondary" @click="close">إغلاق</VBtn>
        <VBtn prepend-icon="tabler-printer" @click="emit('print', props.order.id)">طباعة البوليصة</VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
