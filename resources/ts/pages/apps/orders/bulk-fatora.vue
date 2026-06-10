<script setup lang="ts">
import FatoraSheet from '@/components/prints/FatoraSheet.vue'
import { useApi } from '@/composables/useApi'
import type { ShippingLabelPayload } from '@/utils/printDocuments'

const route = useRoute()
const idsText = (route.query as { ids?: string }).ids || ''
const ids = idsText.split(',').filter(id => id !== '')

const orders = ref<ShippingLabelPayload[]>([])
const settings = ref<Record<string, any> | null>(null)
const isLoading = ref(true)

onMounted(async () => {
  if (!ids.length)
    return

  try {
    const settingsRes = await useApi<Record<string, any>>('/settings').get().json()
    settings.value = settingsRes.data.value

    const orderRequests = ids.map(id =>
      useApi<ShippingLabelPayload>(`/orders/${id}/shipping-label`).get().json(),
    )
    const results = await Promise.all(orderRequests)

    orders.value = results.map(r => r.data.value).filter(Boolean) as ShippingLabelPayload[]
    isLoading.value = false

    setTimeout(() => window.print(), 1500)
  }
  catch (e) {
    console.error('Bulk fatora fetch error:', e)
    isLoading.value = false
  }
})

definePage({
  meta: {
    layout: 'blank',
  },
})
</script>

<template>
  <div
    v-if="!isLoading && orders.length"
    class="bulk-fatora-wrapper"
    dir="rtl"
  >
    <FatoraSheet
      v-for="(order, index) in orders"
      :key="order.order_id ?? order.code ?? index"
      :data="order"
      :settings="settings"
      class="bulk-fatora-page"
    />
  </div>
  <div
    v-else
    class="pa-20 text-center"
  >
    <VProgressCircular
      indeterminate
      color="primary"
    />
    <div class="mt-4">
      تحضير الفواتير للطباعة...
    </div>
  </div>
</template>

<style scoped>
.bulk-fatora-wrapper {
  background: #f1f5f9;
}

.bulk-fatora-page:not(:last-child) {
  page-break-after: always;
  margin-bottom: 12px;
}

@media print {
  .bulk-fatora-wrapper {
    background: transparent;
  }

  .bulk-fatora-page:not(:last-child) {
    page-break-after: always;
  }
}
</style>
