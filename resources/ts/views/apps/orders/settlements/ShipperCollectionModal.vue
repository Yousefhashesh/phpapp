<script setup lang="ts">
import FinancialOrdersSummary from '@/views/apps/orders/components/FinancialOrdersSummary.vue'
import SelectableFinancialOrdersTable from '@/views/apps/orders/components/SelectableFinancialOrdersTable.vue'
import { formatFinancialMoney, resolveSelectedOrderRows } from '@/utils/financialOrders'
import { useApi } from '@/composables/useApi'
import { useUserRole } from '@/composables/useUserRole'

interface Props {
  isDialogVisible: boolean
}

const props = defineProps<Props>()
const emit = defineEmits(['update:isDialogVisible', 'collectionCreated'])
const { isShipperUser, userData } = useUserRole()

const loading = ref(false)
const fetchingOrders = ref(false)
const errorMessages = ref<string[]>([])
const search = ref('')

const formData = ref({
  shipper_user_id: null as number | null,
  collection_date: new Date().toISOString().substr(0, 10),
})

const selectedOrders = ref<Array<number | string>>([])

const shippers = ref<any[]>([])

const loadShippers = async () => {
  if (isShipperUser.value)
    return

  const { data: shippersData } = await useApi<any>('/shippers?eligible_for=collection&per_page=100').get().json()
  const raw = shippersData.value
  const data = Array.isArray(raw)
    ? raw
    : (raw && Array.isArray(raw.data) ? raw.data : [])

  const unique = new Map()
  data.forEach((s: any) => {
    const id = s.user_id || s.id
    if (!unique.has(id)) {
      unique.set(id, {
        ...s,
        name: s.user?.name || 'Unknown',
        user_id: id,
      })
    }
  })

  shippers.value = Array.from(unique.values())
}

watch(() => props.isDialogVisible, visible => {
  if (!visible)
    return

  selectedOrders.value = []
  if (isShipperUser.value) {
    formData.value.shipper_user_id = userData.value?.id ?? null
    fetchEligibleOrders()
  } else {
    loadShippers()
  }
})

const eligibleOrders = ref<any[]>([])

const fetchEligibleOrders = async () => {
  if (!isShipperUser.value && !formData.value.shipper_user_id) {
    eligibleOrders.value = []
    return
  }

  fetchingOrders.value = true
  const query = isShipperUser.value
    ? '/shipper-collections/eligible-orders?per_page=100'
    : `/shipper-collections/eligible-orders?shipper_user_id=${formData.value.shipper_user_id}&per_page=100`
  const { data } = await useApi<any>(query).get().json()
  eligibleOrders.value = data.value?.data || []
  fetchingOrders.value = false
}

watch(() => formData.value.shipper_user_id, fetchEligibleOrders)

const selectedOrderRows = computed(() => resolveSelectedOrderRows(selectedOrders.value, eligibleOrders.value))

const netCollectionAmount = computed(() => {
  const sum = selectedOrderRows.value.reduce((acc, order) => acc + (Number(order.collection_amount) || 0), 0)

  return parseFloat(sum.toFixed(2))
})

const onSubmit = async () => {
  if ((!isShipperUser.value && !formData.value.shipper_user_id) || selectedOrders.value.length === 0) {
    errorMessages.value = ['Please select a shipper and at least one order.']
    return
  }

  loading.value = true
  errorMessages.value = []

  try {
    const orderIds = Array.from(new Set(selectedOrders.value)).map(id => Number(id)).filter(id => Number.isInteger(id))
    const payload: Record<string, any> = {
      collection_date: formData.value.collection_date,
      order_ids: orderIds,
    }

    if (!isShipperUser.value)
      payload.shipper_user_id = formData.value.shipper_user_id

    const { error } = await useApi('/shipper-collections').post(payload).json()

    if (error.value) {
      if ((error.value as any).data?.errors) {
        errorMessages.value = Object.values((error.value as any).data.errors).flat() as string[]
      } else {
        errorMessages.value = [(error.value as any).message || 'Failed to create collection']
      }
    } else {
      emit('collectionCreated')
      emit('update:isDialogVisible', false)
      formData.value.shipper_user_id = null
      selectedOrders.value = []
    }
  } catch (e: any) {
    if (e?.response?.data?.errors) {
      errorMessages.value = Object.values(e.response.data.errors).flat() as string[]
    } else {
      errorMessages.value = ['An error occurred.']
    }
  }
  loading.value = false
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    max-width="1200"
    scrollable
    @update:model-value="val => emit('update:isDialogVisible', val)"
  >
    <VCard title="Create Shipper Collection">
      <VCardText>
        <VAlert
          v-if="errorMessages.length"
          type="error"
          variant="tonal"
          closable
          class="mb-4"
        >
          <ul class="ms-4 mb-0">
            <li
              v-for="msg in errorMessages"
              :key="msg"
            >
              {{ msg }}
            </li>
          </ul>
        </VAlert>

        <VRow>
          <VCol
            v-if="!isShipperUser"
            cols="12"
            md="6"
          >
            <AppAutocomplete
              v-model="formData.shipper_user_id"
              label="Select Shipper"
              placeholder="Search and choose a shipper"
              :items="shippers"
              item-title="name"
              item-value="user_id"
            />
          </VCol>
          <VCol
            cols="12"
            :md="isShipperUser ? 12 : 6"
          >
            <AppTextField
              v-model="formData.collection_date"
              label="Collection Date"
              type="date"
            />
          </VCol>
        </VRow>

        <VDivider class="my-6" />

        <div class="text-h6 mb-4">
          Eligible Orders
        </div>

        <FinancialOrdersSummary :orders="selectedOrderRows" />

        <VRow
          v-if="selectedOrderRows.length > 0"
          class="mb-4"
        >
          <VCol cols="12">
            <div class="text-subtitle-2 mb-1">
              Net Collection
            </div>
            <div class="text-body-1 font-weight-bold text-success">
              {{ formatFinancialMoney(netCollectionAmount) }}
            </div>
          </VCol>
        </VRow>

        <AppTextField
          v-model="search"
          placeholder="Search Order ID, Receiver, etc."
          class="mb-4"
          prepend-inner-icon="tabler-search"
          clearable
        />

        <SelectableFinancialOrdersTable
          v-model="selectedOrders"
          :items="eligibleOrders"
          :loading="fetchingOrders"
          :search="search"
        />
      </VCardText>

      <VCardActions class="pb-6 px-6">
        <VSpacer />
        <VBtn
          color="secondary"
          variant="tonal"
          @click="emit('update:isDialogVisible', false)"
        >
          Cancel
        </VBtn>
        <VBtn
          variant="elevated"
          color="primary"
          :loading="loading"
          :disabled="selectedOrders.length === 0"
          @click="onSubmit"
        >
          Create Collection
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
