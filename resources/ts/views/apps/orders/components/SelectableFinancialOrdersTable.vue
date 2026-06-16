<script setup lang="ts">
import {
  formatFinancialMoney,
  normalizeFinancialOrders,
} from '@/utils/financialOrders'

const props = withDefaults(defineProps<{
  items: Record<string, any>[]
  modelValue: Array<number | string>
  loading?: boolean
  search?: string
  maxHeight?: string
}>(), {
  loading: false,
  search: '',
  maxHeight: '420px',
})

const emit = defineEmits<{
  'update:modelValue': [value: Array<number | string>]
}>()

const selected = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
})

const normalizedItems = computed(() => normalizeFinancialOrders(props.items))

const statusColors: Record<string, string> = {
  OUT_FOR_DELIVERY: 'primary',
  DELIVERED: 'success',
  HOLD: 'warning',
  UNDELIVERED: 'error',
}

const headers = [
  { title: 'ORDER ID', key: 'id', width: '90px' },
  { title: 'CODE', key: 'code', width: '130px' },
  { title: 'CLIENT', key: 'client', width: '150px' },
  { title: 'RECEIVER', key: 'receiver_name', width: '150px' },
  { title: 'AMOUNT', key: 'total_amount', width: '110px' },
  { title: 'SHIPPING FEE', key: 'shipping_fee', width: '120px' },
  { title: 'SHIPPER ACCOUNT', key: 'commission_amount', width: '130px' },
  { title: 'CLIENT ACCOUNT', key: 'company_amount', width: '130px' },
  { title: 'COD', key: 'cod_amount', width: '110px' },
  { title: 'STATUS', key: 'status', width: '120px' },
]
</script>

<template>
  <VDataTable
    v-model="selected"
    :headers="headers"
    :items="normalizedItems"
    :loading="loading"
    :search="search"
    item-value="id"
    show-select
    class="text-no-wrap border rounded selectable-financial-table"
    fixed-header
    :style="{ maxBlockSize: maxHeight }"
    :items-per-page="-1"
    hide-default-footer
  >
    <template #item.id="{ item }">
      <span class="font-weight-bold">#{{ item.id }}</span>
    </template>

    <template #item.code="{ item }">
      <div>{{ item.code || '—' }}</div>
      <div
        v-if="item.external_code"
        class="text-caption text-disabled"
      >
        {{ item.external_code }}
      </div>
    </template>

    <template #item.client="{ item }">
      <div>{{ item.client?.name || '—' }}</div>
      <div
        v-if="item.client?.phone"
        class="text-caption text-disabled"
      >
        {{ item.client.phone }}
      </div>
    </template>

    <template #item.receiver_name="{ item }">
      <div>{{ item.receiver_name || '—' }}</div>
      <div
        v-if="item.phone"
        class="text-caption text-disabled"
      >
        {{ item.phone }}
      </div>
    </template>

    <template #item.total_amount="{ item }">
      {{ formatFinancialMoney(item.total_amount) }}
    </template>

    <template #item.shipping_fee="{ item }">
      <span class="text-error font-weight-medium">{{ formatFinancialMoney(item.shipping_fee) }}</span>
    </template>

    <template #item.commission_amount="{ item }">
      <span class="text-warning font-weight-medium">{{ formatFinancialMoney(item.commission_amount) }}</span>
    </template>

    <template #item.company_amount="{ item }">
      <span class="text-info font-weight-medium">{{ formatFinancialMoney(item.company_amount) }}</span>
    </template>

    <template #item.cod_amount="{ item }">
      <span class="text-success font-weight-bold">{{ formatFinancialMoney(item.cod_amount) }}</span>
    </template>

    <template #item.status="{ item }">
      <VChip
        size="small"
        :color="statusColors[item.status] || 'secondary'"
        variant="tonal"
      >
        {{ item.status }}
      </VChip>
    </template>
  </VDataTable>
</template>

<style scoped>
.selectable-financial-table {
  overflow-x: auto;
}
</style>
