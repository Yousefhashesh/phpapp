<script setup lang="ts">
import SmallShippingLabelSheet from '@/components/prints/SmallShippingLabelSheet.vue'
import { useApi } from '@/composables/useApi'
import type { ShippingLabelPayload } from '@/utils/printDocuments'

const route = useRoute()
const id = (route.params as { id: string }).id

const data = ref<ShippingLabelPayload | null>(null)
const settings = ref<Record<string, any> | null>(null)
const isLoading = ref(true)

const fetchData = async () => {
  try {
    const [orderRes, settingsRes] = await Promise.all([
      useApi<ShippingLabelPayload>(`/orders/${id}/shipping-label`).get().json(),
      useApi<Record<string, any>>('/settings').get().json(),
    ])

    data.value = orderRes.data.value
    settings.value = settingsRes.data.value
    isLoading.value = false

    setTimeout(() => window.print(), 1500)
  }
  catch (e) {
    console.error('Small shipping label fetch error:', e)
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
    v-if="!isLoading && data"
    class="small-label-wrapper"
    dir="rtl"
  >
    <SmallShippingLabelSheet
      :data="data"
      :settings="settings"
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
      تحضير ليبل الشحن الصغير...
    </div>
  </div>
</template>

<style scoped>
.small-label-wrapper {
  background: #fff;
  padding: 2mm;
  max-width: 100mm;
  margin: 0 auto;
}

@media print {
  .small-label-wrapper {
    padding: 0;
    max-width: none;
  }
}
</style>
