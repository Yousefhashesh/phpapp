<script setup lang="ts">
import EditableFinancialOrdersTable from '@/views/apps/orders/components/EditableFinancialOrdersTable.vue'
import { useApi } from '@/composables/useApi'
import { useFlashHighlight } from '@/composables/useFlashHighlight'

type DocumentType = 'shipper-collection' | 'client-settlement'
type ListRouteName = 'apps-orders-shipper-collections' | 'apps-orders-client-settlements'

const props = defineProps<{
  documentType: DocumentType
}>()

const route = useRoute()
const router = useRouter()
const { can } = useAbility()
const { flash, isFlashing } = useFlashHighlight()

const documentId = computed(() => Number(String(route.params.id ?? 0)))

const listRouteName = computed<ListRouteName>(() =>
  props.documentType === 'client-settlement'
    ? 'apps-orders-client-settlements'
    : 'apps-orders-shipper-collections',
)

const config = computed(() => {
  if (props.documentType === 'client-settlement') {
    return {
      apiBase: '/client-settlements',
      title: 'Client Settlement',
      dateField: 'settlement_date',
      permissionPrefix: 'client-settlement',
      printType: 'settlement',
    }
  }

  return {
    apiBase: '/shipper-collections',
    title: 'Shipper Collection',
    dateField: 'collection_date',
    permissionPrefix: 'shipper-collection',
    printType: 'collection',
  }
})

const document = ref<any>(null)
const isLoading = ref(true)
const processingAction = ref(false)

const fetchDocument = async () => {
  isLoading.value = true
  const { data } = await useApi<any>(`${config.value.apiBase}/${documentId.value}`).get().json()
  document.value = data.value || null
  isLoading.value = false
}

onMounted(fetchDocument)

const financialSummary = computed(() => {
  const orders = document.value?.orders ?? []
  let totalAmount = 0
  let totalFees = 0
  let totalCod = 0
  let shipperFees = 0

  for (const order of orders) {
    totalAmount += Number(order.total_amount) || 0
    totalFees += Number(order.shipping_fee) || 0
    totalCod += Number(order.cod_amount) || 0
    shipperFees += Number(order.commission_amount) || 0
  }

  return { totalAmount, totalFees, totalCod, shipperFees }
})

const formatSummaryMoney = (value: number) =>
  `EGP ${value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`

const onOrderUpdated = async (order: any) => {
  if (!document.value?.orders)
    return

  const index = document.value.orders.findIndex((o: any) => o.id === order.id)
  if (index !== -1)
    document.value.orders[index] = { ...document.value.orders[index], ...order }

  // Immediately update local summary for UX responsiveness
  document.value.total_amount = financialSummary.value.totalAmount
  if (props.documentType === 'shipper-collection') {
    document.value.shipper_fees = financialSummary.value.shipperFees
    document.value.net_amount = financialSummary.value.totalAmount - financialSummary.value.shipperFees
  } else {
    document.value.fees = financialSummary.value.totalFees
    document.value.net_amount = financialSummary.value.totalCod
  }

  flash(`doc-${document.value.id}`)

  // Refetch from server to get accurate backend-calculated totals
  try {
    const { data } = await useApi<any>(`${config.value.apiBase}/${documentId.value}`).get().json()
    if (data.value) {
      const serverOrders = data.value.orders
      document.value = { ...data.value }
      if (serverOrders)
        document.value.orders = serverOrders
    }
  } catch { /* keep local state on error */ }
}

const removeOrder = async (orderId: number) => {
  if (!document.value)
    return

  processingAction.value = true
  const { data, error } = await useApi(`${config.value.apiBase}/${document.value.id}/orders/${orderId}`)
    .delete()
    .json()

  if (!error.value) {
    if (data.value?.deleted) {
      router.push({ name: listRouteName.value })
    } else {
      document.value = data.value?.data || data.value
    }
  }
  processingAction.value = false
}

const printDocument = () => {
  window.open(`/apps/orders/print/${documentId.value}?type=${config.value.printType}`, '_blank')
}

const goBack = () => {
  router.push({ name: listRouteName.value })
}

const isPending = computed(() => document.value?.status === 'PENDING')
const canEditOrders = computed(() => can(`${config.value.permissionPrefix}.update` as any, 'all' as any) && isPending.value)
const canView = computed(() => can(`${config.value.permissionPrefix}.view` as any, 'all' as any))
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between flex-wrap gap-3 mb-4">
      <div class="d-flex align-center gap-2">
        <IconBtn @click="goBack">
          <VIcon icon="tabler-arrow-right" />
        </IconBtn>
        <h4 class="text-h4 mb-0">
          {{ config.title }} #{{ documentId }}
        </h4>
      </div>
      <div class="d-flex gap-2">
        <VBtn
          v-if="canView"
          color="primary"
          variant="tonal"
          prepend-icon="tabler-file-invoice"
          @click="printDocument"
        >
          Print
        </VBtn>
      </div>
    </div>

    <VProgressLinear
      v-if="isLoading"
      indeterminate
      color="primary"
      class="mb-4"
    />

    <VCard v-else-if="document">
      <VCardText>
        <VRow :class="{ 'cell-flash': isFlashing(`doc-${document.id}`) }">
          <VCol
            cols="12"
            md="3"
          >
            <div class="text-subtitle-2 mb-1">
              {{ documentType === 'shipper-collection' ? 'Shipper' : 'Client' }}
            </div>
            <div class="text-body-1 font-weight-bold">
              {{ document.shipper?.name || document.client?.name || '—' }}
            </div>
          </VCol>
          <VCol
            cols="12"
            md="3"
          >
            <div class="text-subtitle-2 mb-1">
              Date
            </div>
            <div class="text-body-1 font-weight-bold">
              {{ document[config.dateField] || '—' }}
            </div>
          </VCol>
          <VCol
            cols="12"
            md="3"
          >
            <div class="text-subtitle-2 mb-1">
              Status
            </div>
            <VChip
              size="small"
              variant="tonal"
            >
              {{ document.status }}
            </VChip>
          </VCol>
          <VCol
            cols="12"
            md="3"
          >
            <div class="text-subtitle-2 mb-1">
              Approval
            </div>
            <VChip
              size="small"
              variant="tonal"
            >
              {{ document.approval_status }}
            </VChip>
          </VCol>
        </VRow>

        <VDivider class="my-4" />

        <VRow>
          <VCol
            cols="12"
            md="3"
          >
            <div class="text-subtitle-2 mb-1">
              Total Amount
            </div>
            <div class="text-h6 font-weight-bold">
              {{ formatSummaryMoney(financialSummary.totalAmount) }}
            </div>
          </VCol>
          <VCol
            cols="12"
            md="3"
          >
            <div class="text-subtitle-2 mb-1">
              Shipping Fees
            </div>
            <div class="text-h6 font-weight-bold text-error">
              {{ formatSummaryMoney(financialSummary.totalFees) }}
            </div>
          </VCol>
          <VCol
            v-if="documentType === 'shipper-collection'"
            cols="12"
            md="3"
          >
            <div class="text-subtitle-2 mb-1">
              Shipper Fees
            </div>
            <div class="text-h6 font-weight-bold text-warning">
              {{ formatSummaryMoney(financialSummary.shipperFees) }}
            </div>
          </VCol>
          <VCol
            cols="12"
            md="3"
          >
            <div class="text-subtitle-2 mb-1">
              {{ documentType === 'shipper-collection' ? 'Net Collection' : 'COD' }}
            </div>
            <div class="text-h6 font-weight-bold text-success">
              {{ formatSummaryMoney(documentType === 'shipper-collection' ? Number(document.net_amount) || 0 : financialSummary.totalCod) }}
            </div>
          </VCol>
        </VRow>

        <VDivider class="my-4" />

        <div class="text-h6 mb-2">
          Orders
          <span
            v-if="canEditOrders"
            class="text-caption text-disabled ms-2"
          >اضغط على أي قيمة لتعديلها</span>
          <VChip
            v-if="!isPending && can(`${config.permissionPrefix}.update` as any, 'all' as any)"
            size="small"
            color="warning"
            variant="tonal"
            class="ms-2"
          >
            التعديل متاح فقط عند حالة PENDING
          </VChip>
        </div>

        <EditableFinancialOrdersTable
          :orders="document.orders || []"
          :editable="canEditOrders"
          :show-actions="canEditOrders"
          :processing-action="processingAction"
          @order-updated="onOrderUpdated"
          @remove="removeOrder"
        />
      </VCardText>
    </VCard>
  </div>
</template>

<style scoped>
:deep(.cell-flash) {
  animation: cell-flash 2s ease-out;
}

@keyframes cell-flash {
  0% {
    box-shadow: inset 0 0 0 2px rgb(var(--v-theme-primary));
  }

  100% {
    box-shadow: inset 0 0 0 0 transparent;
  }
}
</style>
