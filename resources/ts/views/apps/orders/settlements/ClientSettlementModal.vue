<script setup lang="ts">
import FinancialOrdersSummary from '@/views/apps/orders/components/FinancialOrdersSummary.vue'
import SelectableFinancialOrdersTable from '@/views/apps/orders/components/SelectableFinancialOrdersTable.vue'
import { resolveSelectedOrderRows, summarizeFinancialOrders } from '@/utils/financialOrders'
import { useApi } from '@/composables/useApi'
import { useUserRole } from '@/composables/useUserRole'

interface Props {
  isDialogVisible: boolean
}

const props = defineProps<Props>()
const emit = defineEmits(['update:isDialogVisible', 'settlementCreated'])
const { isClientUser, userData } = useUserRole()

const loading = ref(false)
const fetchingOrders = ref(false)
const errorMessages = ref<string[]>([])
const search = ref('')

const formData = ref({
  client_user_id: null as number | null,
  settlement_date: new Date().toISOString().substr(0, 10),
})

const selectedOrders = ref<Array<number | string>>([])

const clients = ref<any[]>([])

const loadClients = async () => {
  if (isClientUser.value)
    return

  const { data: clientsData } = await useApi<any>('/clients?eligible_for=settlement&per_page=100').get().json()
  const raw = clientsData.value
  const data = Array.isArray(raw)
    ? raw
    : (raw && Array.isArray(raw.data) ? raw.data : [])

  const unique = new Map()
  data.forEach((c: any) => {
    const id = c.user_id || c.id
    if (!unique.has(id)) {
      unique.set(id, {
        ...c,
        name: c.user?.name || 'Unknown',
        user_id: id,
      })
    }
  })

  clients.value = Array.from(unique.values())
}

watch(() => props.isDialogVisible, visible => {
  if (!visible)
    return

  selectedOrders.value = []
  if (isClientUser.value) {
    formData.value.client_user_id = userData.value?.id ?? null
    fetchEligibleOrders()
  } else {
    loadClients()
  }
})

const eligibleOrders = ref<any[]>([])

const fetchEligibleOrders = async () => {
  if (!isClientUser.value && !formData.value.client_user_id) {
    eligibleOrders.value = []
    return
  }

  fetchingOrders.value = true
  const query = isClientUser.value
    ? '/client-settlements/eligible-orders?per_page=100'
    : `/client-settlements/eligible-orders?client_user_id=${formData.value.client_user_id}&per_page=100`

  const { data } = await useApi<any>(query).get().json()
  eligibleOrders.value = data.value?.data || []
  fetchingOrders.value = false
}

watch(() => formData.value.client_user_id, fetchEligibleOrders)

const selectedOrderRows = computed(() => resolveSelectedOrderRows(selectedOrders.value, eligibleOrders.value))

const financialSummary = computed(() => summarizeFinancialOrders(selectedOrderRows.value))

const onSubmit = async () => {
  if ((!isClientUser.value && !formData.value.client_user_id) || selectedOrders.value.length === 0) {
    errorMessages.value = ['Please select a client and at least one order.']
    return
  }

  loading.value = true
  errorMessages.value = []

  try {
    const orderIds = Array.from(new Set(selectedOrders.value)).map(id => Number(id)).filter(id => Number.isInteger(id))

    const payload: Record<string, any> = {
      settlement_date: formData.value.settlement_date,
      total_amount: financialSummary.value.totalAmount,
      number_of_orders: orderIds.length,
      order_ids: orderIds,
    }

    if (!isClientUser.value) {
      payload.client_user_id = formData.value.client_user_id
    }

    const { error } = await useApi('/client-settlements').post(payload).json()

    if (error.value) {
      if (error.value.data?.errors) {
        errorMessages.value = Object.values(error.value.data.errors).flat() as string[]
      } else {
        errorMessages.value = ['Failed to create settlement']
      }
    } else {
      emit('settlementCreated')
      emit('update:isDialogVisible', false)
      formData.value.client_user_id = null
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
    <VCard title="Create Client Settlement">
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
            v-if="!isClientUser"
            cols="12"
            md="6"
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
            :md="isClientUser ? 12 : 6"
          >
            <AppTextField
              v-model="formData.settlement_date"
              label="Settlement Date"
              type="date"
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
          Create Settlement
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
