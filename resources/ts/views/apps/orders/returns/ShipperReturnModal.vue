<script setup lang="ts">
import FinancialOrdersSummary from '@/views/apps/orders/components/FinancialOrdersSummary.vue'
import SelectableFinancialOrdersTable from '@/views/apps/orders/components/SelectableFinancialOrdersTable.vue'
import { resolveSelectedOrderRows } from '@/utils/financialOrders'
import { useApi } from '@/composables/useApi'
import { useUserRole } from '@/composables/useUserRole'

interface Props {
  isDialogVisible: boolean
}

const props = defineProps<Props>()
const emit = defineEmits(['update:isDialogVisible', 'returnCreated'])
const { isShipperUser, userData } = useUserRole()

const loading = ref(false)
const fetchingOrders = ref(false)
const errorMessages = ref<string[]>([])
const search = ref('')

const formData = ref({
  shipper_user_id: null as number | null,
  return_date: new Date().toISOString().substr(0, 10),
  notes: '',
  client_tracking_number: '',
  internal_tracking_number: '',
})

const selectedOrders = ref<Array<number | string>>([])

const shippers = ref<any[]>([])

const loadShippers = async () => {
  if (isShipperUser.value)
    return

  const { data: shippersData } = await useApi<any>('/shippers?eligible_for=return&per_page=100').get().json()
  const raw = shippersData.value
  const data = Array.isArray(raw)
    ? raw
    : (raw && Array.isArray(raw.data) ? raw.data : [])

  shippers.value = data.map((s: any) => ({
    ...s,
    name: s.user?.name || 'Unknown',
    user_id: s.user_id || s.id,
  }))
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
    ? '/shipper-returns/eligible-orders?per_page=100000'
    : `/shipper-returns/eligible-orders?shipper_user_id=${formData.value.shipper_user_id}&per_page=100000`
  const { data } = await useApi<any>(query).get().json()
  eligibleOrders.value = data.value?.data || []
  fetchingOrders.value = false
}

watch(() => formData.value.shipper_user_id, fetchEligibleOrders)

const selectedOrderRows = computed(() => resolveSelectedOrderRows(selectedOrders.value, eligibleOrders.value))

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
      return_date: formData.value.return_date,
      number_of_orders: orderIds.length,
      notes: formData.value.notes || null,
      client_tracking_number: formData.value.client_tracking_number || null,
      internal_tracking_number: formData.value.internal_tracking_number || null,
      order_ids: orderIds,
    }

    if (!isShipperUser.value)
      payload.shipper_user_id = formData.value.shipper_user_id

    const { error } = await useApi('/shipper-returns').post(payload).json()

    if (error.value) {
      if (error.value.data?.errors) {
        errorMessages.value = Object.values(error.value.data.errors).flat() as string[]
      } else {
        errorMessages.value = ['Failed to create return']
      }
    } else {
      emit('returnCreated')
      emit('update:isDialogVisible', false)
      formData.value.shipper_user_id = null
      formData.value.notes = ''
      formData.value.client_tracking_number = ''
      formData.value.internal_tracking_number = ''
      selectedOrders.value = []
    }
  } catch (e) {
    errorMessages.value = ['An error occurred.']
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
    <VCard title="Create Shipper Return">
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
            md="4"
          >
            <AppAutocomplete
              v-model="formData.shipper_user_id"
              label="Select Shipper"
              placeholder="Search and choose a shipper"
              :items="shippers"
              item-title="name"
              item-value="user_id"
              clearable
            />
          </VCol>
          <VCol
            cols="12"
            md="4"
          >
            <AppTextField
              v-model="formData.client_tracking_number"
              label="Client Tracking Number"
              placeholder="Enter client tracking number"
              clearable
            />
          </VCol>
          <VCol
            cols="12"
            md="4"
          >
            <AppTextField
              v-model="formData.internal_tracking_number"
              label="Internal Tracking Number"
              placeholder="Enter internal tracking number"
              clearable
            />
          </VCol>
          <VCol
            cols="12"
            md="4"
          >
            <AppTextField
              v-model="formData.return_date"
              label="Return Date"
              type="date"
            />
          </VCol>
          <VCol
            cols="12"
            md="8"
          >
            <AppTextField
              v-model="formData.notes"
              label="Notes (Optional)"
              placeholder="Add notes..."
            />
          </VCol>
        </VRow>

        <VDivider class="my-6" />

        <div class="text-h6 mb-4">
          Eligible Orders
        </div>

        <FinancialOrdersSummary :orders="selectedOrderRows" />

        <AppTextField
          v-model="search"
          placeholder="Search Order ID, Client, Code, etc."
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
          Create Return
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
