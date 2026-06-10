<script setup lang="ts">
import OrderInvoiceSheet from '@/components/prints/OrderInvoiceSheet.vue'
import { useApi } from '@/composables/useApi'

const route = useRoute()
const id = (route.params as { id: string }).id

const order = ref<Record<string, any> | null>(null)
const settings = ref<Record<string, any> | null>(null)
const isLoading = ref(true)

onMounted(async () => {
  const [orderRes, settingsRes] = await Promise.all([
    useApi<Record<string, any>>(`/orders/${id}`).get().json(),
    useApi<Record<string, any>>('/settings').get().json(),
  ])

  order.value = orderRes.data.value
  settings.value = settingsRes.data.value
  isLoading.value = false

  setTimeout(() => window.print(), 1200)
})

definePage({
  meta: {
    layout: 'blank',
  },
})
</script>

<template>
  <OrderInvoiceSheet
    v-if="!isLoading && order"
    :order="order"
    :settings="settings"
  />
  <div
    v-else
    class="pa-20 text-center"
  >
    <VProgressCircular
      indeterminate
      color="primary"
    />
  </div>
</template>
