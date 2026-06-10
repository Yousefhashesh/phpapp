<script setup lang="ts">
import { useAbility } from '@casl/vue'

definePage({
  meta: {
    action: 'manage',
    subject: 'expense.page',
  },
})

interface UserMini {
  id: number
  name: string
}

interface ExpenseCategory {
  id: number
  name?: string
}

interface ExpenseItem {
  id?: number
  code?: string | null
  title?: string
  amount?: number | string
  expense_date?: string | null
  status?: string | null
  category_id?: number | null
  category?: ExpenseCategory | null
  notes?: string | null
  created_by_user?: UserMini | null
  approved_by_user?: UserMini | null
}

interface ApiValidationError {
  message?: string
  errors?: Record<string, string[]>
}

interface ApiCollectionResponse<T> {
  data?: T[]
}

interface AclMatrixResponse {
  pages?: Record<string, boolean>
  actions?: Record<string, boolean>
}

const ability = useAbility()

const canCreateExpense = computed(() => ability.can('manage', 'expense.create'))
const canUpdateExpense = computed(() => ability.can('manage', 'expense.update'))
const canDeleteExpense = computed(() => ability.can('manage', 'expense.delete'))
const canViewPage = computed(() => ability.can('manage', 'expense.page'))

const isLoading = ref(false)
const isSaving = ref(false)
const isDeleteLoading = ref<number | null>(null)

const alertType = ref<'success' | 'error'>('success')
const alertMessage = ref('')

const expenses = ref<ExpenseItem[]>([])
const categories = ref<ExpenseCategory[]>([])

const isCreateDialogOpen = ref(false)
const isEditMode = ref(false)
const editingExpenseId = ref<number | null>(null)
const searchQuery = ref('')
const selectedStatus = ref<string>('all')
const selectedCategory = ref<number | 'all'>('all')
const itemsPerPage = ref(10)

const newExpense = ref({
  title: '',
  amount: null as number | null,
  expense_date: '',
  category_id: null as number | null,
  notes: '',
  status: 'PENDING',
})

const fieldErrors = ref<Record<string, string[]>>({})

const hasAnyVisibleData = computed(() => expenses.value.some(item => Object.keys(item || {}).length > 1))

const statusOptions = [
  { title: 'Pending', value: 'PENDING' },
  { title: 'Approved', value: 'APPROVED' },
  { title: 'Rejected', value: 'REJECTED' },
  { title: 'Paid', value: 'PAID' },
  { title: 'Cancelled', value: 'CANCELLED' },
]

const categoryOptions = computed(() => categories.value.map(category => ({
  title: category.name || `Category #${category.id}`,
  value: category.id,
})))

const statusFilterOptions = computed(() => [
  { title: 'All Statuses', value: 'all' },
  ...statusOptions,
])

const categoryFilterOptions = computed(() => [
  { title: 'All Categories', value: 'all' },
  ...categoryOptions.value,
])

const STORAGE_KEY = 'expenses-visible-columns'

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'TITLE', key: 'title' },
  { title: 'AMOUNT', key: 'amount' },
  { title: 'DATE', key: 'expense_date' },
  { title: 'CATEGORY', key: 'category' },
  { title: 'STATUS', key: 'status' },
  { title: 'CREATED BY', key: 'created_by_user' },
]

const columnPermissions: Record<string, string> = {
  title: 'expense.column.title.view',
  amount: 'expense.column.amount.view',
  expense_date: 'expense.column.expense_date.view',
  category: 'expense.column.category_id.view',
  status: 'expense.column.status.view',
  created_by_user: 'expense.column.created_by.view',
}

const visibleHeaderKeys = ref(JSON.parse(localStorage.getItem(STORAGE_KEY) || JSON.stringify([...headers.map(h => h.key), 'actions'])))

const activeHeaders = computed(() => {
  const hArr = headers.filter(h => {
    if (!visibleHeaderKeys.value.includes(h.key)) return false
    const perm = columnPermissions[h.key]
    if (perm && !ability.can('manage', perm)) return false
    return true
  })

  if (visibleHeaderKeys.value.includes('actions') && (canUpdateExpense.value || canDeleteExpense.value)) {
    hArr.push({ title: 'ACTIONS', key: 'actions' })
  }

  return hArr
})

const filteredHeadersForMenu = computed(() => {
  const menuHeaders = headers.filter(h => {
    const perm = columnPermissions[h.key]
    return !perm || ability.can('manage', perm)
  })
  menuHeaders.push({ title: 'ACTIONS', key: 'actions' })
  return menuHeaders
})

watch(visibleHeaderKeys, (newVal) => {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal))
})

const showAlert = (message: string, type: 'success' | 'error' = 'success') => {
  alertType.value = type
  alertMessage.value = message
}

const buildAbilityRulesFromAcl = (acl: any) => {
  const grantedPermissions = acl.ability_rules || [
    ...Object.entries(acl.pages ?? {}),
    ...Object.entries(acl.actions ?? {}),
  ]
    .filter(([, allowed]) => !!allowed)
    .map(([permission]) => permission)

  if (!grantedPermissions.length)
    return [{ action: 'manage', subject: 'all' }]

  return grantedPermissions.map((permission: string) => ({ action: 'manage', subject: permission }))
}

const syncAbilityFromAcl = async () => {
  try {
    const acl = await $api<AclMatrixResponse>('/acl')
    const rules = buildAbilityRulesFromAcl(acl)

    useLocalStorage('userAbilityRules', []).value = rules as any
    ability.update(rules as any)
  }
  catch {
    // Keep existing ability when ACL endpoint fails temporarily.
  }
}

const normalizeCollectionResponse = <T>(response: T[] | ApiCollectionResponse<T> | unknown): T[] => {
  if (Array.isArray(response))
    return response

  if (response && typeof response === 'object') {
    const maybeData = (response as ApiCollectionResponse<T>).data

    if (Array.isArray(maybeData))
      return maybeData
  }

  return []
}

const resetCreateForm = () => {
  newExpense.value = {
    title: '',
    amount: null,
    expense_date: '',
    category_id: null,
    notes: '',
    status: 'PENDING',
  }

  isEditMode.value = false
  editingExpenseId.value = null
  fieldErrors.value = {}
}

const fetchExpenses = async () => {
  isLoading.value = true
  try {
    const response = await $api<ExpenseItem[] | ApiCollectionResponse<ExpenseItem>>('/expenses')
    expenses.value = normalizeCollectionResponse<ExpenseItem>(response)

    if (expenses.value.length > 0 && !hasAnyVisibleData.value)
      showAlert('Expenses were loaded, but your account cannot view expense columns. Please enable expense.column.*.view permissions.', 'error')
  }
  catch (error: any) {
    const statusCode = error?.status || error?.response?.status
    const message = error?.data?.message
      || (statusCode === 403 ? 'Missing permission: expense.page or expense.view.' : 'Failed to load expenses.')

    showAlert(message, 'error')
  }
  finally {
    isLoading.value = false
  }
}

const fetchCategories = async () => {
  try {
    const response = await $api<ExpenseCategory[] | ApiCollectionResponse<ExpenseCategory>>('/expense-categories')
    categories.value = normalizeCollectionResponse<ExpenseCategory>(response)
  }
  catch {
    categories.value = []
  }
}

const submitCreateExpense = async () => {
  fieldErrors.value = {}
  isSaving.value = true

  try {
    const payload = {
      title: newExpense.value.title,
      amount: newExpense.value.amount,
      expense_date: newExpense.value.expense_date,
      category_id: newExpense.value.category_id,
      notes: newExpense.value.notes || null,
      status: newExpense.value.status,
    }

    if (isEditMode.value && editingExpenseId.value) {
      await $api(`/expenses/${editingExpenseId.value}`, {
        method: 'PATCH',
        body: payload,
      })
      showAlert('Expense updated successfully.', 'success')
    }
    else {
      await $api('/expenses', {
        method: 'POST',
        body: payload,
      })
      showAlert('Expense added successfully.', 'success')
    }

    isCreateDialogOpen.value = false
    resetCreateForm()
    await fetchExpenses()
  }
  catch (error: any) {
    const apiError = error?.data as ApiValidationError | undefined
    const message = apiError?.message || (isEditMode.value ? 'Failed to update expense.' : 'Failed to add expense.')
    fieldErrors.value = apiError?.errors || {}
    showAlert(message, 'error')
  }
  finally {
    isSaving.value = false
  }
}

const openCreateExpenseDialog = () => {
  resetCreateForm()
  isCreateDialogOpen.value = true
}

const openEditExpenseDialog = (item: ExpenseItem) => {
  if (!item.id)
    return

  isEditMode.value = true
  editingExpenseId.value = item.id
  fieldErrors.value = {}

  newExpense.value = {
    title: item.title || '',
    amount: item.amount !== undefined && item.amount !== null ? Number(item.amount) : null,
    expense_date: item.expense_date || '',
    category_id: item.category_id ?? null,
    notes: item.notes || '',
    status: item.status || 'PENDING',
  }

  isCreateDialogOpen.value = true
}

const deleteExpense = async (expenseId: number | undefined) => {
  if (!expenseId)
    return

  const confirmed = window.confirm('Are you sure you want to delete this expense?')
  if (!confirmed)
    return

  isDeleteLoading.value = expenseId
  try {
    await $api(`/expenses/${expenseId}`, { method: 'DELETE' })
    showAlert('Expense deleted successfully.', 'success')
    await fetchExpenses()
  }
  catch (error: any) {
    const message = error?.data?.message || 'Failed to delete expense.'
    showAlert(message, 'error')
  }
  finally {
    isDeleteLoading.value = null
  }
}

const formatAmount = (value: number | string | undefined) => {
  const numeric = Number(value ?? 0)
  if (!Number.isFinite(numeric))
    return '-'

  return `${new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(numeric)} EGP`
}

const formatDate = (date: string | null | undefined) => {
  if (!date)
    return '-'

  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime()))
    return date

  return parsed.toLocaleDateString('en-GB')
}

const statusColor = (status: string | null | undefined) => {
  switch (status) {
    case 'APPROVED':
      return 'success'
    case 'REJECTED':
      return 'error'
    case 'PAID':
      return 'info'
    case 'CANCELLED':
      return 'secondary'
    default:
      return 'warning'
  }
}

const tableItems = computed(() => expenses.value.map(item => ({
  ...item,
  amountText: formatAmount(item.amount),
  dateText: formatDate(item.expense_date),
  categoryText: item.category?.name || '-',
  createdByText: item.created_by_user?.name || '-',
})))

const filteredTableItems = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  return tableItems.value.filter(item => {
    const statusMatch = selectedStatus.value === 'all' || item.status === selectedStatus.value
    const categoryMatch = selectedCategory.value === 'all' || item.category_id === selectedCategory.value

    if (!statusMatch || !categoryMatch)
      return false

    if (!query)
      return true

    const haystack = [
      String(item.id ?? ''),
      item.title ?? '',
      item.code ?? '',
      item.amountText ?? '',
      item.dateText ?? '',
      item.categoryText ?? '',
      item.status ?? '',
      item.createdByText ?? '',
    ]
      .join(' ')
      .toLowerCase()

    return haystack.includes(query)
  })
})

onMounted(async () => {
  await syncAbilityFromAcl()

  if (!canViewPage.value) {
    showAlert('You do not have expense.page permission.', 'error')

    return
  }

  await Promise.all([
    fetchExpenses(),
    fetchCategories(),
  ])
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VAlert
        v-if="alertMessage"
        :type="alertType"
        variant="tonal"
        closable
        class="mb-4"
        @click:close="alertMessage = ''"
      >
        {{ alertMessage }}
      </VAlert>

      <VCard>
        <VCardItem class="d-flex flex-wrap align-center gap-3 justify-space-between">
          <div>
            <VCardTitle>Expenses</VCardTitle>
            <VCardSubtitle>Manage expense records from API</VCardSubtitle>
          </div>
        </VCardItem>

        <VCardText class="d-flex align-center justify-space-between flex-wrap gap-4">
          <div />

          <div class="d-flex align-center gap-3 flex-wrap">
            <AppTextField
              v-model="searchQuery"
              placeholder="Search expense"
              prepend-inner-icon="tabler-search"
              style="inline-size: 15.625rem;"
            />

            <AppSelect
              v-model="selectedStatus"
              :items="statusFilterOptions"
              style="inline-size: 12rem;"
            />

            <AppSelect
              :model-value="selectedCategory"
              :items="categoryFilterOptions"
              style="inline-size: 13rem;"
              @update:model-value="selectedCategory = $event === 'all' ? 'all' : Number($event)"
            />

            <VBtn
              v-if="canCreateExpense"
              class="add-expense-btn"
              prepend-icon="tabler-plus"
              @click="openCreateExpenseDialog"
            >
              Add Expense
            </VBtn>

            <!-- 👉 Column Visibility Toggle -->
            <VMenu :close-on-content-click="false">
              <template #activator="{ props }">
                <VBtn icon variant="tonal" color="secondary" v-bind="props">
                  <VIcon icon="tabler-layout-columns" />
                </VBtn>
              </template>
              <VList class="pa-2">
                <VListItem v-for="h in filteredHeadersForMenu" :key="h.key" density="compact">
                  <VCheckbox v-model="visibleHeaderKeys" :value="h.key" :label="h.title" hide-details density="compact" />
                </VListItem>
              </VList>
            </VMenu>
          </div>
        </VCardText>

        <VDivider />

        <VCardText>
          <div v-if="isLoading" class="d-flex justify-center py-12">
            <VProgressCircular indeterminate color="primary" />
          </div>

          <div v-else class="table-wrap">
            <VDataTable
              :headers="activeHeaders"
              :items="filteredTableItems"
              v-model:items-per-page="itemsPerPage"
              height="420"
              fixed-header
              class="text-no-wrap"
            >
              <template #item.amount="{ item }">
                <span>{{ item.amountText }}</span>
              </template>

              <template #item.expense_date="{ item }">
                <span>{{ item.dateText }}</span>
              </template>

              <template #item.category="{ item }">
                <span>{{ item.categoryText }}</span>
              </template>

              <template #item.status="{ item }">
                <VChip
                  :color="statusColor(item.status)"
                  class="font-weight-medium"
                  size="small"
                  variant="tonal"
                >
                  {{ item.status || 'PENDING' }}
                </VChip>
              </template>

              <template #item.created_by_user="{ item }">
                <span>{{ item.createdByText }}</span>
              </template>

              <template #item.actions="{ item }">
                <div class="d-flex align-center">
                  <VBtn
                    v-if="canUpdateExpense"
                    icon
                    variant="text"
                    color="primary"
                    @click="openEditExpenseDialog(item)"
                  >
                    <VIcon icon="tabler-edit" />
                  </VBtn>

                  <VBtn
                    v-if="canDeleteExpense"
                    icon
                    variant="text"
                    color="error"
                    :loading="isDeleteLoading === item.id"
                    @click="deleteExpense(item.id)"
                  >
                    <VIcon icon="tabler-trash" />
                  </VBtn>
                </div>
              </template>

              <template #no-data>
                <div class="text-center py-6 text-medium-emphasis">
                  No expenses found.
                </div>
              </template>
            </VDataTable>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <VDialog
    v-model="isCreateDialogOpen"
    max-width="640"
    persistent
  >
    <VCard>
      <VCardItem>
        <VCardTitle>{{ isEditMode ? 'Edit Expense' : 'Add Expense' }}</VCardTitle>
        <template #append>
          <IconBtn
            @click="isCreateDialogOpen = false; resetCreateForm()"
          >
            <VIcon icon="tabler-x" />
          </IconBtn>
        </template>
      </VCardItem>

      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <AppTextField
              v-model="newExpense.title"
              label="Title"
              placeholder="Expense title"
              :error-messages="fieldErrors.title"
            />
          </VCol>

          <VCol cols="12" md="6">
            <AppTextField
              v-model.number="newExpense.amount"
              type="number"
              min="0"
              step="0.01"
              label="Amount"
              placeholder="0.00"
              :error-messages="fieldErrors.amount"
            />
          </VCol>

          <VCol cols="12" md="6">
            <AppTextField
              v-model="newExpense.expense_date"
              type="date"
              label="Expense Date"
              :error-messages="fieldErrors.expense_date"
            />
          </VCol>

          <VCol cols="12" md="6">
            <AppSelect
              v-model="newExpense.category_id"
              :items="categoryOptions"
              clearable
              label="Category"
              :error-messages="fieldErrors.category_id"
            />
          </VCol>

          <VCol cols="12" md="6">
            <AppSelect
              v-model="newExpense.status"
              :items="statusOptions"
              label="Status"
              :error-messages="fieldErrors.status"
            />
          </VCol>

          <VCol cols="12">
            <AppTextField
              v-model="newExpense.notes"
              label="Notes"
              placeholder="Optional notes"
              :error-messages="fieldErrors.notes"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VCardActions class="justify-end px-6 pb-6">
        <VBtn
          variant="outlined"
          color="secondary"
          @click="isCreateDialogOpen = false; resetCreateForm()"
        >
          Cancel
        </VBtn>
        <VBtn
          color="primary"
          :loading="isSaving"
          @click="submitCreateExpense"
        >
          {{ isEditMode ? 'Update' : 'Save' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.table-wrap {
  overflow-x: auto;
}

.add-expense-btn {
  border: 1px solid rgba(var(--v-theme-primary), 0.35);
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.16), rgba(var(--v-theme-primary), 0.32));
  box-shadow: 0 8px 20px rgba(var(--v-theme-primary), 0.22);
  transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
}

.add-expense-btn:hover {
  box-shadow: 0 12px 28px rgba(var(--v-theme-primary), 0.3);
  filter: saturate(1.08);
  transform: translateY(-1px);
}
</style>
