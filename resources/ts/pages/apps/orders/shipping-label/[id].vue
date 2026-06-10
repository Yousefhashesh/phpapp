<script setup lang="ts">
import ShippingLabelSheet from '@/components/prints/ShippingLabelSheet.vue'
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
    console.error('Shipping label fetch error:', e)
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
    class="shipping-label-wrapper"
    dir="rtl"
  >
    <ShippingLabelSheet
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
      تحضير بوليصة الشحن...
    </div>
  </div>
</template>

<style scoped>
.shipping-label-wrapper {
  background: #fff;
  padding: 2mm;
}

@media print {
  .shipping-label-wrapper {
    padding: 0;
  }
}
</style>
