<script setup lang="ts">
import FinancialOrdersSummary from '@/views/apps/orders/components/FinancialOrdersSummary.vue'
import SelectableFinancialOrdersTable from '@/views/apps/orders/components/SelectableFinancialOrdersTable.vue'
import { resolveSelectedOrderRows } from '@/utils/financialOrders'
import { useApi } from '@/composables/useApi'

interface Props {
  isDialogVisible: boolean
}

const props = defineProps<Props>()
const emit = defineEmits(['update:isDialogVisible', 'returnCreated'])

const loading = ref(false)
const fetchingOrders = ref(false)
const errorMessages = ref<string[]>([])
const search = ref('')

const formData = ref({
  client_user_id: null as number | null,
  return_date: new Date().toISOString().substr(0, 10),
  notes: '',
})

const selectedOrders = ref<Array<number | string>>([])

const clients = ref<any[]>([])

const loadClients = async () => {
  const { data: clientsData } = await useApi<any>('/clients?eligible_for=return&per_page=100').get().json()
  const raw = clientsData.value
  const data = Array.isArray(raw)
    ? raw
    : (raw && Array.isArray(raw.data) ? raw.data : [])

  clients.value = data.map((c: any) => ({
    ...c,
    name: c.user?.name || 'Unknown',
    user_id: c.user_id || c.id,
  }))
}

watch(() => props.isDialogVisible, visible => {
  if (visible) loadClients()
})

const eligibleOrders = ref<any[]>([])

const fetchEligibleOrders = async () => {
  if (!formData.value.client_user_id) {
    eligibleOrders.value = []
    return
  }

  fetchingOrders.value = true
  const { data } = await useApi<any>(`/client-returns/eligible-orders?client_user_id=${formData.value.client_user_id}&per_page=100000`).get().json()
  eligibleOrders.value = data.value?.data || []
  fetchingOrders.value = false
}

watch(() => formData.value.client_user_id, fetchEligibleOrders)

const selectedOrderRows = computed(() => resolveSelectedOrderRows(selectedOrders.value, eligibleOrders.value))

const onSubmit = async () => {
  if (!formData.value.client_user_id || selectedOrders.value.length === 0) {
    errorMessages.value = ['Please select a client and at least one order.']
    return
  }

  loading.value = true
  errorMessages.value = []

  try {
    const orderIds = Array.from(new Set(selectedOrders.value)).map(id => Number(id)).filter(id => Number.isInteger(id))

    const { error } = await useApi('/client-returns').post({
      client_user_id: formData.value.client_user_id,
      return_date: formData.value.return_date,
      number_of_orders: orderIds.length,
      notes: formData.value.notes || null,
      order_ids: orderIds,
    }).json()

    if (error.value) {
      if (error.value.data?.errors) {
        errorMessages.value = Object.values(error.value.data.errors).flat() as string[]
      } else {
        errorMessages.value = ['Failed to create client return']
      }
    } else {
      emit('returnCreated')
      emit('update:isDialogVisible', false)
      formData.value.client_user_id = null
      formData.value.notes = ''
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
    <VCard title="Create Client Return">
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
            cols="12"
            md="4"
          >
            <AppAutocomplete
              v-model="formData.client_user_id"
              label="Select Client"
              placeholder="Search and choose a client"
              :items="clients"
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
              v-model="formData.return_date"
              label="Return Date"
              type="date"
            />
          </VCol>
          <VCol
            cols="12"
            md="4"
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
          placeholder="Search Order ID, Receiver, Code, etc."
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
          Create Client Return
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
