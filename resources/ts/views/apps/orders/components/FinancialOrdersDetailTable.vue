<script setup lang="ts">
const props = withDefaults(defineProps<{
  orders: any[]
  showStatus?: boolean
  showActions?: boolean
  processingAction?: boolean
}>(), {
  showStatus: true,
  showActions: false,
  processingAction: false,
})

const emit = defineEmits<{
  remove: [orderId: number]
}>()

const statusColors: Record<string, string> = {
  OUT_FOR_DELIVERY: 'primary',
  DELIVERED: 'success',
  HOLD: 'warning',
  UNDELIVERED: 'error',
}

const formatMoney = (value: number | string | null | undefined) => {
  const amount = Number(value)

  return `EGP ${Number.isFinite(amount) ? amount.toFixed(2) : '0.00'}`
}

const clientPhone = (order: any) => order.client?.phone || ''
</script>

<template>
  <div class="financial-orders-table">
    <VTable
      class="text-no-wrap"
      density="compact"
    >
      <thead>
        <tr>
          <th>ORDER ID</th>
          <th>CODE</th>
          <th>CLIENT</th>
          <th>RECEIVER</th>
          <th>AMOUNT</th>
          <th>SHIPPING FEE</th>
          <th>SHIPPER ACCOUNT</th>
          <th>CLIENT ACCOUNT</th>
          <th>COD</th>
          <th v-if="showStatus">
            STATUS
          </th>
          <th v-if="showActions">
            ACTIONS
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="order in orders"
          :key="order.id"
        >
          <td class="font-weight-bold">
            #{{ order.id }}
          </td>
          <td>
            <div>{{ order.code || '—' }}</div>
            <div
              v-if="order.external_code"
              class="text-caption text-disabled"
            >
              {{ order.external_code }}
            </div>
          </td>
          <td>
            <div>{{ order.client?.name || '—' }}</div>
            <div
              v-if="clientPhone(order)"
              class="text-caption text-disabled"
            >
              {{ clientPhone(order) }}
            </div>
          </td>
          <td>
            <div>{{ order.receiver_name || '—' }}</div>
            <div
              v-if="order.phone"
              class="text-caption text-disabled"
            >
              {{ order.phone }}
            </div>
          </td>
          <td>{{ formatMoney(order.total_amount) }}</td>
          <td class="text-error font-weight-medium">
            {{ formatMoney(order.shipping_fee) }}
          </td>
          <td class="text-warning font-weight-medium">
            {{ formatMoney(order.commission_amount) }}
          </td>
          <td class="text-info font-weight-medium">
            {{ formatMoney(order.company_amount) }}
          </td>
          <td class="text-success font-weight-bold">
            {{ formatMoney(order.cod_amount) }}
          </td>
          <td v-if="showStatus">
            <VChip
              size="small"
              :color="statusColors[order.status] || 'secondary'"
              variant="tonal"
            >
              {{ order.status }}
            </VChip>
          </td>
          <td v-if="showActions">
            <IconBtn
              size="small"
              color="error"
              variant="tonal"
              :disabled="processingAction"
              @click="emit('remove', order.id)"
            >
              <VIcon icon="tabler-trash" />
              <VTooltip activator="parent">
                Remove
              </VTooltip>
            </IconBtn>
          </td>
        </tr>
        <tr v-if="!orders.length">
          <td
            :colspan="showStatus ? (showActions ? 11 : 10) : (showActions ? 10 : 9)"
            class="text-center text-disabled py-6"
          >
            No orders found.
          </td>
        </tr>
      </tbody>
    </VTable>
  </div>
</template>

<style scoped>
.financial-orders-table {
  overflow-x: auto;
}
</style>
