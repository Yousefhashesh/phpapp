<script setup lang="ts">
interface Props {
  userData: {
    id: number
    account_type: number // 1: Client, 2: Shipper
  }
}

const props = defineProps<Props>()

const searchQuery = ref('')
const selectedStatus = ref()

// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()

// Update data table options
const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

const isLoading = ref(false)

// 👉 Determine Endpoint and Base Params
const endpoint = computed(() => {
  if (props.userData.account_type === 1) return '/client-settlements'
  if (props.userData.account_type === 2) return '/shipper-collections'
  return '/apps/invoice' // Default fallback
})

const baseParams = computed(() => {
  if (props.userData.account_type === 1) return { client_user_id: props.userData.id }
  if (props.userData.account_type === 2) return { shipper_user_id: props.userData.id }
  return {}
})

// 👉 headers
const headers = computed(() => {
  const common = [
    { title: '#ID', key: 'id' },
    { title: 'Date', key: props.userData.account_type === 1 ? 'settlement_date' : 'collection_date' },
    { title: 'Orders', key: 'number_of_orders' },
    { title: 'Total', key: 'total_amount' },
    { title: 'Net', key: 'net_amount' },
    { title: 'Status', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]
  return common
})

// 👉 Fetch Data
const { data: listData, execute: fetchList } = await useApi<any>(createUrl(endpoint.value, {
  query: {
    ...baseParams.value,
    search: searchQuery,
    status: selectedStatus,
    per_page: itemsPerPage,
    page,
  },
}))

const items = computed(() => listData.value?.data || listData.value || [])
const totalItems = computed(() => listData.value?.total || 0)

// 👉 Status variant resolver
const resolveStatusVariant = (status: string) => {
  if (status === 'COMPLETED' || status === 'Paid' || status === 'APPROVED')
    return { variant: 'success', icon: 'tabler-circle-check' }
  if (status === 'PENDING')
    return { variant: 'warning', icon: 'tabler-clock' }
  if (status === 'CANCELLED' || status === 'REJECTED')
    return { variant: 'error', icon: 'tabler-circle-x' }

  return { variant: 'secondary', icon: 'tabler-help' }
}

const computedMoreList = computed(() => {
  return (paramId: number) => ([
    { title: 'Download', value: 'download', prependIcon: 'tabler-download' },
  ])
})

const viewFullInvoice = (id: number) => {
  const type = props.userData.account_type === 1 ? 'client-settlements' : 'shipper-collections'
  const filterKey = props.userData.account_type === 1 ? 'client_user_id' : 'shipper_user_id'
  window.location.href = `/apps/orders/${type}?${filterKey}=${props.userData.id}`
}
</script>

<template>
  <section v-if="items">
    <VCard id="invoice-list">
      <VCardText>
        <div class="d-flex align-center justify-space-between flex-wrap gap-4">
          <div class="text-h5">
            {{ props.userData.account_type === 1 ? 'Settlements' : 'Collections' }} List
          </div>
          <div class="d-flex align-center gap-x-4">
            <AppSelect
              :model-value="itemsPerPage"
              :items="[
                { value: 10, title: '10' },
                { value: 25, title: '25' },
                { value: 50, title: '50' },
                { value: 100, title: '100' },
                { value: -1, title: 'All' },
              ]"
              style="inline-size: 6.25rem;"
              @update:model-value="itemsPerPage = parseInt($event, 10)"
            />
          </div>
        </div>
      </VCardText>

      <VDivider />

      <!-- SECTION Datatable -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :loading="isLoading"
        :items-length="totalItems"
        :headers="headers"
        :items="items"
        item-value="id"
        class="text-no-wrap text-sm rounded-0"
        @update:options="updateOptions"
      >
        <!-- id -->
        <template #item.id="{ item }: { item: any }">
          <span class="text-primary font-weight-bold">#{{ item.id }}</span>
        </template>

        <!-- Status -->
        <template #item.status="{ item }: { item: any }">
          <VAvatar
            :size="28"
            :color="resolveStatusVariant(item.status).variant"
            variant="tonal"
          >
            <VIcon
              :size="16"
              :icon="resolveStatusVariant(item.status).icon"
            />
          </VAvatar>
          <span class="ms-2 text-capitalize">{{ item.status }}</span>
        </template>

        <!-- Total -->
        <template #item.total_amount="{ item }: { item: any }">
          {{ item.total_amount }} EGP
        </template>

        <!-- Net -->
        <template #item.net_amount="{ item }: { item: any }">
          {{ item.net_amount }} EGP
        </template>

        <!-- Date -->
        <template #item.settlement_date="{ item }: { item: any }">
          {{ new Date(item.settlement_date).toLocaleDateString() }}
        </template>

        <template #item.collection_date="{ item }: { item: any }">
          {{ new Date(item.collection_date).toLocaleDateString() }}
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }: { item: any }">
          <IconBtn @click="viewFullInvoice(item.id)">
            <VIcon icon="tabler-eye" />
          </IconBtn>
        </template>
        
        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="totalItems"
          />
        </template>
      </VDataTableServer>
      <!-- !SECTION -->
    </VCard>
  </section>
</template>

<style lang="scss">
#invoice-list {
  .invoice-list-actions {
    inline-size: 8rem;
  }

  .invoice-list-search {
    inline-size: 12rem;
  }
}
</style>
