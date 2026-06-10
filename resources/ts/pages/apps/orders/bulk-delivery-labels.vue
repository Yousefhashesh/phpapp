<script setup lang="ts">
import SmallShippingLabelSheet from '@/components/prints/SmallShippingLabelSheet.vue'
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
    console.error('Bulk Delivery Labels Fetch Error:', e)
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
    <div class="labels-container">
      <div
        v-for="(data, index) in labelsData"
        :key="`${data.order_id ?? data.code ?? index}`"
        class="label-item"
      >
        <SmallShippingLabelSheet
          :data="data"
          :settings="settings"
        />
      </div>
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
      تحضير ليبلات الشحن للطباعة...
    </div>
  </div>
</template>

<style scoped>
.bulk-print-wrapper {
  background: #fff;
  padding: 2mm;
}

.labels-container {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  padding: 8px;
  box-sizing: border-box;
  width: 100%;
}

.label-item {
  box-sizing: border-box;
}

@media print {
  .bulk-print-wrapper {
    padding: 0;
  }

  .labels-container {
    padding: 0;
    gap: 8mm;
  }

  .label-item {
    page-break-inside: avoid;
  }
}
</style>
