<script setup lang="ts">
import OrderInvoiceSheet from '@/components/prints/OrderInvoiceSheet.vue'
import { useApi } from '@/composables/useApi'

const route = useRoute()
const idsText = (route.query as { ids?: string }).ids || ''
const ids = idsText.split(',').filter(id => id !== '')

const orders = ref<Record<string, any>[]>([])
const settings = ref<Record<string, any> | null>(null)
const isLoading = ref(true)

onMounted(async () => {
  if (!ids.length)
    return

  try {
    const settingsRes = await useApi<Record<string, any>>('/settings').get().json()
    settings.value = settingsRes.data.value

    const orderRequests = ids.map(id => useApi<Record<string, any>>(`/orders/${id}`).get().json())
    const results = await Promise.all(orderRequests)

    orders.value = results.map(r => r.data.value).filter(Boolean)
    isLoading.value = false

    setTimeout(() => window.print(), 1500)
  }
  catch (e) {
    console.error('Bulk invoice fetch error:', e)
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
    class="bulk-invoice-wrapper"
    dir="rtl"
  >
    <OrderInvoiceSheet
      v-for="order in orders"
      :key="order.id"
      :order="order"
      :settings="settings"
      class="bulk-invoice-page"
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
.bulk-invoice-wrapper {
  background: #f1f5f9;
}

.bulk-invoice-page:not(:last-child) {
  page-break-after: always;
  margin-bottom: 12px;
}

@media print {
  .bulk-invoice-wrapper {
    background: transparent;
  }

  .bulk-invoice-page:not(:last-child) {
    page-break-after: always;
  }
}
</style>
