<script setup lang="ts">
import OrderTrackingReportSheet from '@/components/prints/OrderTrackingReportSheet.vue'
import { useApi } from '@/composables/useApi'
import {
  normalizeHistoryResponse,
  sortLogsChronologically,
  type OrderTrackingLog,
} from '@/utils/orderTrackingReport'

const route = useRoute()
const id = (route.params as { id: string }).id

const order = ref<Record<string, any> | null>(null)
const settings = ref<Record<string, any> | null>(null)
const logs = ref<OrderTrackingLog[]>([])
const isLoading = ref(true)

onMounted(async () => {
  try {
    const [orderRes, settingsRes, historyRes] = await Promise.all([
      useApi<Record<string, any>>(`/orders/${id}`).get().json(),
      useApi<Record<string, any>>('/settings').get().json(),
      useApi(`/orders/${id}/history?per_page=100`).get().json(),
    ])

    order.value = orderRes.data.value
    settings.value = settingsRes.data.value
    logs.value = sortLogsChronologically(normalizeHistoryResponse(historyRes.data.value))
  }
  catch (e) {
    console.error('Order tracking report fetch error:', e)
  }
  finally {
    isLoading.value = false
    setTimeout(() => window.print(), 1400)
  }
})

definePage({
  meta: {
    layout: 'blank',
  },
})
</script>

<template>
  <OrderTrackingReportSheet
    v-if="!isLoading && order"
    :order="order"
    :settings="settings"
    :logs="logs"
  />
  <div
    v-else
    class="pa-20 text-center"
  >
    <VProgressCircular
      indeterminate
      color="primary"
    />
    <p class="mt-4 text-body-2 text-secondary">
      جاري تحميل تقرير التتبع...
    </p>
  </div>
</template>
