<script setup lang="ts">
import ShippingLabelSheet from '@/components/prints/ShippingLabelSheet.vue'
import { useApi } from '@/composables/useApi'
import type { ShippingLabelPayload } from '@/utils/printDocuments'

const route = useRoute()
const idsText = (route.query as { ids?: string }).ids || ''
const ids = idsText.split(',').filter(id => id !== '')

const labelsData = ref<ShippingLabelPayload[]>([])
const settings = ref<Record<string, any> | null>(null)
const isLoading = ref(true)

const fetchData = async () => {
  if (!ids.length)
    return

  try {
    const settingsRes = await useApi<Record<string, any>>('/settings').get().json()
    settings.value = settingsRes.data.value

    const labelRequests = ids.map(id =>
      useApi<ShippingLabelPayload>(`/orders/${id}/shipping-label`).get().json(),
    )
    const results = await Promise.all(labelRequests)

    labelsData.value = results
      .map(r => r.data.value)
      .filter((d): d is ShippingLabelPayload => d != null)
    isLoading.value = false

    setTimeout(() => window.print(), 1500)
  }
  catch (e) {
    console.error('Bulk Print Fetch Error:', e)
    isLoading.value = false
  }
}

onMounted(fetchData)

definePage({
  meta: {
    layout: 'blank',
  },
})
</script>

<template>
  <div
    v-if="!isLoading && labelsData.length"
    class="bulk-print-wrapper"
    dir="rtl"
  >
    <div
      v-for="(data, index) in labelsData"
      :key="`${data.order_id ?? data.code ?? index}`"
      class="shipping-card-container"
    >
      <ShippingLabelSheet
        :data="data"
        :settings="settings"
      />
    </div>
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
      تحضير البوالص للطباعة...
    </div>
  </div>
</template>

<style scoped>
.bulk-print-wrapper {
  background: #fff;
  padding: 2mm;
}

.shipping-card-container:not(:last-child) {
  page-break-after: always;
  margin-bottom: 12px;
}

@media print {
  .bulk-print-wrapper {
    padding: 0;
  }

  .shipping-card-container {
    page-break-after: always;
  }

  .shipping-card-container:last-child {
    page-break-after: auto;
  }
}
</style>
