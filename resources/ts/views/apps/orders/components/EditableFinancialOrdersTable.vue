<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import { useFlashHighlight } from '@/composables/useFlashHighlight'

const props = withDefaults(defineProps<{
  orders: any[]
  editable?: boolean
  showStatus?: boolean
  showActions?: boolean
  processingAction?: boolean
}>(), {
  editable: false,
  showStatus: true,
  showActions: false,
  processingAction: false,
})

const emit = defineEmits<{
  remove: [orderId: number]
  orderUpdated: [order: any]
}>()

const { flash, isFlashing } = useFlashHighlight()
const savingKey = ref<string | null>(null)
const activeCell = ref<string | null>(null)
const draftValues = ref<Record<string, string | number>>({})
const inputRefs = ref<Record<string, any>>({})

const statusColors: Record<string, string> = {
  OUT_FOR_DELIVERY: 'primary',
  DELIVERED: 'success',
  HOLD: 'warning',
  UNDELIVERED: 'error',
}

type FieldType = 'text' | 'number' | 'note'

interface EditableField {
  key: string
  label: string
  type: FieldType
  class?: string
  inputClass?: string
}

const editableFields: EditableField[] = [
  { key: 'receiver_name', label: 'RECEIVER', type: 'text', inputClass: 'editable-input--text' },
  { key: 'phone', label: 'PHONE', type: 'text', inputClass: 'editable-input--text' },
  { key: 'total_amount', label: 'AMOUNT', type: 'number', inputClass: 'editable-input--money' },
  { key: 'shipping_fee', label: 'SHIPPING FEE', type: 'number', class: 'text-error', inputClass: 'editable-input--money' },
  { key: 'commission_amount', label: 'SHIPPER ACCOUNT', type: 'number', class: 'text-warning', inputClass: 'editable-input--money' },
  { key: 'company_amount', label: 'CLIENT ACCOUNT', type: 'number', class: 'text-info', inputClass: 'editable-input--money' },
  { key: 'cod_amount', label: 'COD', type: 'number', class: 'text-success font-weight-bold', inputClass: 'editable-input--money' },
  { key: 'order_note', label: 'ملاحظة الأوردر', type: 'note', inputClass: 'editable-input--note' },
  { key: 'latest_status_note', label: 'ملاحظة الحالة', type: 'note', inputClass: 'editable-input--note' },
]

const cellKey = (orderId: number, field: string) => `${orderId}:${field}`

const isEditing = (orderId: number, field: string) => activeCell.value === cellKey(orderId, field)

const getDraft = (order: any, field: string) => {
  const key = cellKey(order.id, field)
  if (draftValues.value[key] !== undefined)
    return draftValues.value[key]

  return order[field] ?? ''
}

const setDraft = (order: any, field: string, value: string | number) => {
  draftValues.value[cellKey(order.id, field)] = value
}

const formatMoney = (value: number | string | null | undefined) => {
  const amount = Number(value)

  return `EGP ${Number.isFinite(amount) ? amount.toFixed(2) : '0.00'}`
}

const displayValue = (order: any, field: EditableField) => {
  const value = order[field.key]

  if (field.type === 'number')
    return formatMoney(value)

  return value || '—'
}

const clientPhone = (order: any) => order.client?.phone || ''

const setInputRef = (key: string, el: any) => {
  if (el)
    inputRefs.value[key] = el
  else
    delete inputRefs.value[key]
}

const focusInput = async (key: string) => {
  await nextTick()
  const component = inputRefs.value[key]
  const input = component?.$el?.querySelector('input, textarea')
  input?.focus()
  input?.select?.()
}

const startEdit = async (order: any, field: string) => {
  if (!props.editable || savingKey.value)
    return

  const key = cellKey(order.id, field)
  activeCell.value = key
  await focusInput(key)
}

const stopEdit = () => {
  activeCell.value = null
}

const isNumericField = (field: string) =>
  field.includes('amount') || field.includes('fee') || field === 'cod_amount'

const saveField = async (order: any, field: string) => {
  if (!props.editable)
    return

  const key = cellKey(order.id, field)
  const raw = draftValues.value[key] ?? order[field]
  const payloadValue = isNumericField(field) ? Number(raw) : raw

  stopEdit()

  if (payloadValue === order[field]) {
    delete draftValues.value[key]
    return
  }

  savingKey.value = key

  try {
    const { data, error } = await useApi(`/orders/${order.id}`)
      .patch({ [field]: payloadValue })
      .json()

console.log("orders :" , data.value?.data)

    if (!error.value && data.value?.data) {
      Object.assign(order, data.value.data)
      delete draftValues.value[key]
      emit('orderUpdated', data.value.data)
      flash(key)
    }
  } finally {
    savingKey.value = null
  }
}

const onCellBlur = (order: any, field: string) => {
  if (isEditing(order.id, field))
    saveField(order, field)
}

const emptyColspan = computed(() => {
  let cols = 3 + editableFields.length
  if (props.showStatus)
    cols += 1
  if (props.showActions)
    cols += 1

  return cols
})
</script>

<template>
  <div class="financial-orders-table">
    <VTable
      class="editable-financial-table text-no-wrap"
      density="comfortable"
    >
      <thead>
        <tr>
          <th class="col-id">
            ORDER ID
          </th>
          <th class="col-code">
            CODE
          </th>
          <th class="col-client">
            CLIENT
          </th>
          <th class="col-shipper">
            shipper
          </th>
          <th class="col-governorate">
            GOVERNORATE
          </th>
          <th
            v-for="field in editableFields"
            :key="field.key"
            :class="field.type === 'note' ? 'col-note' : field.type === 'number' ? 'col-money' : 'col-text'"
          >
            {{ field.label }}
          </th>
          <th
            v-if="showStatus"
            class="col-status"
          >
            STATUS
          </th>
          <th
            v-if="showActions"
            class="col-actions"
          >
            ACTIONS
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="order in orders"
          :key="order.id"
        >
          <td class="font-weight-bold col-id">
            #{{ order.id }}
            <VChip
              v-if="order.is_client_settled"
              size="x-small"
              color="info"
              variant="tonal"
              class="ms-1"
            >
              تسوية عميل
            </VChip>
          </td>
          <td class="col-code">
            <div>{{ order.code || '—' }}</div>
            <div
              v-if="order.external_code"
              class="text-caption text-disabled"
            >
              {{ order.external_code }}
            </div>
          </td>
          <td class="col-client">
            <div>{{ order.client?.name || '—' }}</div>
            <div
              v-if="clientPhone(order)"
              class="text-caption text-disabled"
            >
              {{ clientPhone(order) }}
            </div>
          </td>

<td class="col-shipper">
  <div>{{ order.shipper?.name || '—' }}</div>
</td>
          <td class="col-governorate">
  <div>{{ order.governorate_name || order.governorate || '—' }} </div>
</td>



          <td
            v-for="field in editableFields"
            :key="field.key"
            :class="[
              field.class,
              field.type === 'note' ? 'col-note' : field.type === 'number' ? 'col-money' : 'col-text',
              { 'cell-flash': isFlashing(cellKey(order.id, field.key)) },
            ]"
          >
            <VTextarea
              v-if="editable && isEditing(order.id, field.key) && field.type === 'note'"
              :ref="(el: any) => setInputRef(cellKey(order.id, field.key), el)"
              :model-value="String(getDraft(order, field.key))"
              rows="2"
              auto-grow
              density="comfortable"
              hide-details
              variant="outlined"
              class="editable-input"
              :class="field.inputClass"
              :loading="savingKey === cellKey(order.id, field.key)"
              @update:model-value="setDraft(order, field.key, $event)"
              @blur="onCellBlur(order, field.key)"
              @keydown.esc.stop="stopEdit()"
            />
            <VTextField
              v-else-if="editable && isEditing(order.id, field.key)"
              :ref="(el: any) => setInputRef(cellKey(order.id, field.key), el)"
              :model-value="getDraft(order, field.key)"
              :type="field.type === 'number' ? 'number' : 'text'"
              density="comfortable"
              hide-details
              variant="outlined"
              class="editable-input"
              :class="field.inputClass"
              :loading="savingKey === cellKey(order.id, field.key)"
              @update:model-value="setDraft(order, field.key, $event)"
              @blur="onCellBlur(order, field.key)"
              @keydown.enter="($event.target as HTMLInputElement).blur()"
              @keydown.esc.stop="stopEdit()"
            />
            <div
              v-else
              class="editable-cell"
              :class="{
                'editable-cell--clickable': editable,
                'editable-cell--note': field.type === 'note',
                'editable-cell--money': field.type === 'number',
              }"
              @click="startEdit(order, field.key)"
            >
              {{ displayValue(order, field) }}
            </div>
          </td>

          <td
            v-if="showStatus"
            class="col-status"
          >
            <VChip
              size="small"
              :color="statusColors[order.status] || 'secondary'"
              variant="tonal"
            >
              {{ order.status }}
            </VChip>
          </td>
          <td
            v-if="showActions"
            class="col-actions"
          >
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
            :colspan="emptyColspan"
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

.editable-financial-table {
  min-inline-size: 100%;
}

.col-id {
  min-inline-size: 72px;
}

.col-code {
  min-inline-size: 110px;
}

.col-client {
  min-inline-size: 140px;
}

.col-text {
  min-inline-size: 130px;
}

.col-money {
  min-inline-size: 150px;
}

.col-note {
  min-inline-size: 220px;
  max-inline-size: 280px;
}

.col-status {
  min-inline-size: 120px;
}

.col-actions {
  min-inline-size: 72px;
}

.editable-cell {
  display: flex;
  align-items: center;
  min-block-size: 44px;
  padding-block: 8px;
  padding-inline: 10px;
  border-radius: 6px;
  line-height: 1.35;
  white-space: nowrap;
}

.editable-cell--note {
  align-items: flex-start;
  white-space: normal;
  word-break: break-word;
}

.editable-cell--money {
  font-size: 0.95rem;
  font-variant-numeric: tabular-nums;
}

.editable-cell--clickable {
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.editable-cell--clickable:hover {
  background: rgba(var(--v-theme-primary), 0.08);
}

:deep(.editable-input .v-field) {
  font-size: 0.95rem;
}

:deep(.editable-input--text .v-field) {
  min-inline-size: 120px;
}

:deep(.editable-input--money .v-field) {
  min-inline-size: 140px;
}

:deep(.editable-input--money input) {
  font-size: 1rem;
  font-variant-numeric: tabular-nums;
}

:deep(.editable-input--note .v-field) {
  min-inline-size: 200px;
}

:deep(.editable-input--note textarea) {
  font-size: 0.9rem;
  line-height: 1.4;
}

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
