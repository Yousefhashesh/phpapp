<script setup lang="ts">
import { formatFinancialMoney, summarizeFinancialOrders } from '@/utils/financialOrders'

const props = defineProps<{
  orders: Record<string, any>[]
}>()

const summary = computed(() => summarizeFinancialOrders(props.orders))
</script>

<template>
  <VRow
    v-if="summary.count > 0"
    class="mb-4"
  >
    <VCol
      cols="12"
      md="3"
    >
      <div class="text-subtitle-2 mb-1">
        Orders Selected
      </div>
      <div class="text-body-1 font-weight-bold">
        {{ summary.count }}
      </div>
    </VCol>
    <VCol
      cols="12"
      md="3"
    >
      <div class="text-subtitle-2 mb-1">
        Total Amount
      </div>
      <div class="text-body-1 font-weight-bold">
        {{ formatFinancialMoney(summary.totalAmount) }}
      </div>
    </VCol>
    <VCol
      cols="12"
      md="3"
    >
      <div class="text-subtitle-2 mb-1">
        Total Fees
      </div>
      <div class="text-body-1 font-weight-bold text-error">
        {{ formatFinancialMoney(summary.totalFees) }}
      </div>
    </VCol>
    <VCol
      cols="12"
      md="3"
    >
      <div class="text-subtitle-2 mb-1">
        COD Amount
      </div>
      <div class="text-body-1 font-weight-bold text-success">
        {{ formatFinancialMoney(summary.totalCod) }}
      </div>
    </VCol>
  </VRow>
</template>
