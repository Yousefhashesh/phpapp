<script setup lang="ts">
import { useAbility } from '@casl/vue'

definePage({
  meta: {
    action: 'manage',
    subject: 'expense-category.page',
  },
})

interface ExpenseCategoryItem {
  id?: number
  name?: string | null
  notes?: string | null
  is_active?: boolean | null
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

const canCreateCategory = computed(() => ability.can('manage', 'expense-category.create'))
const canUpdateCategory = computed(() => ability.can('manage', 'expense-category.update'))
const canDeleteCategory = computed(() => ability.can('manage', 'expense-category.delete'))
const canViewPage = computed(() => ability.can('manage', 'expense-category.page'))

const isLoading = ref(false)
const isSaving = ref(false)
const isDeleteLoading = ref<number | null>(null)

const alertType = ref<'success' | 'error'>('success')
const alertMessage = ref('')

const categories = ref<ExpenseCategoryItem[]>([])

const isCategoryDialogOpen = ref(false)
const isEditMode = ref(false)
const editingCategoryId = ref<number | null>(null)

const searchQuery = ref('')
const selectedActive = ref<'all' | 'active' | 'inactive'>('all')
const itemsPerPage = ref(10)

const form = ref({
  name: '',
  notes: '',
  is_active: true,
})

const fieldErrors = ref<Record<string, string[]>>({})

const tableHeaders = computed(() => {
  const baseHeaders = [
    { title: 'ID', key: 'id' },
    { title: 'NAME', key: 'name' },
    { title: 'NOTES', key: 'notes' },
    { title: 'STATUS', key: 'is_active' },
  ]

  if (canUpdateCategory.value || canDeleteCategory.value)
    return [...baseHeaders, { title: 'ACTIONS', key: 'actions', sortable: false }]

  return baseHeaders
})

const activeFilterOptions = [
  { title: 'All Statuses', value: 'all' },
  { title: 'Active', value: 'active' },
  { title: 'Inactive', value: 'inactive' },
]

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

const resetForm = () => {
  form.value = {
    name: '',
    notes: '',
    is_active: true,
  }
  fieldErrors.value = {}
  editingCategoryId.value = null
  isEditMode.value = false
}

const fetchCategories = async () => {
  isLoading.value = true
  try {
    const response = await $api<ExpenseCategoryItem[] | ApiCollectionResponse<ExpenseCategoryItem>>('/expense-categories')
    categories.value = normalizeCollectionResponse<ExpenseCategoryItem>(response)
  }
  catch (error: any) {
    const statusCode = error?.status || error?.response?.status
    const message = error?.data?.message
      || (statusCode === 403 ? 'Missing permission: expense-category.page or expense-category.view.' : 'Failed to load expense categories.')

    showAlert(message, 'error')
  }
  finally {
    isLoading.value = false
  }
}

const submitCategory = async () => {
  fieldErrors.value = {}
  isSaving.value = true

  try {
    const payload = {
      name: form.value.name,
      notes: form.value.notes || null,
      is_active: form.value.is_active,
    }

    if (isEditMode.value && editingCategoryId.value) {
      await $api(`/expense-categories/${editingCategoryId.value}`, {
        method: 'PATCH',
        body: payload,
      })
      showAlert('Expense category updated successfully.', 'success')
    }
    else {
      await $api('/expense-categories', {
        method: 'POST',
        body: payload,
      })
      showAlert('Expense category added successfully.', 'success')
    }

    isCategoryDialogOpen.value = false
    resetForm()
    await fetchCategories()
  }
  catch (error: any) {
    const apiError = error?.data as ApiValidationError | undefined
    const message = apiError?.message || 'Failed to save expense category.'
    fieldErrors.value = apiError?.errors || {}
    showAlert(message, 'error')
  }
  finally {
    isSaving.value = false
  }
}

const openCreateDialog = () => {
  resetForm()
  isCategoryDialogOpen.value = true
}

const openEditDialog = (item: ExpenseCategoryItem) => {
  isEditMode.value = true
  editingCategoryId.value = item.id ?? null
  form.value = {
    name: item.name || '',
    notes: item.notes || '',
    is_active: item.is_active !== false,
  }
  fieldErrors.value = {}
  isCategoryDialogOpen.value = true
}

const deleteCategory = async (categoryId: number | undefined) => {
  if (!categoryId)
    return

  const confirmed = window.confirm('Are you sure you want to delete this expense category?')
  if (!confirmed)
    return

  isDeleteLoading.value = categoryId
  try {
    await $api(`/expense-categories/${categoryId}`, { method: 'DELETE' })
    showAlert('Expense category deleted successfully.', 'success')
    await fetchCategories()
  }
  catch (error: any) {
    const message = error?.data?.message || 'Failed to delete expense category.'
    showAlert(message, 'error')
  }
  finally {
    isDeleteLoading.value = null
  }
}

const statusColor = (isActive: boolean | null | undefined) => {
  if (isActive === true)
    return 'success'
  if (isActive === false)
    return 'secondary'

  return 'warning'
}

const statusText = (isActive: boolean | null | undefined) => {
  if (isActive === true)
    return 'ACTIVE'
  if (isActive === false)
    return 'INACTIVE'

  return 'UNKNOWN'
}

const tableItems = computed(() => categories.value.map(item => ({
  ...item,
  nameText: item.name || '-',
  notesText: item.notes || '-',
  isActiveText: statusText(item.is_active),
})))

const filteredTableItems = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  return tableItems.value.filter(item => {
    const activeMatch = selectedActive.value === 'all'
      || (selectedActive.value === 'active' && item.is_active === true)
      || (selectedActive.value === 'inactive' && item.is_active === false)

    if (!activeMatch)
      return false

    if (!query)
      return true

    const haystack = [
      String(item.id ?? ''),
      item.nameText ?? '',
      item.notesText ?? '',
      item.isActiveText ?? '',
    ]
      .join(' ')
      .toLowerCase()

    return haystack.includes(query)
  })
})

onMounted(async () => {
  await syncAbilityFromAcl()

  if (!canViewPage.value) {
    showAlert('You do not have expense-category.page permission.', 'error')

    return
  }

  await fetchCategories()
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
            <VCardTitle>Expense Categories</VCardTitle>
            <VCardSubtitle>Manage expense categories from API</VCardSubtitle>
          </div>
        </VCardItem>

        <VCardText class="d-flex align-center justify-space-between flex-wrap gap-4">
          <div />

          <div class="d-flex align-center gap-3 flex-wrap">
            <AppTextField
              v-model="searchQuery"
              placeholder="Search category"
              prepend-inner-icon="tabler-search"
              style="inline-size: 15.625rem;"
            />

            <AppSelect
              v-model="selectedActive"
              :items="activeFilterOptions"
              style="inline-size: 12rem;"
            />

            <VBtn
              v-if="canCreateCategory"
              class="add-category-btn"
              prepend-icon="tabler-plus"
              @click="openCreateDialog"
            >
              Add Category
            </VBtn>
          </div>
        </VCardText>

        <VDivider />

        <VCardText>
          <div v-if="isLoading" class="d-flex justify-center py-12">
            <VProgressCircular indeterminate color="primary" />
          </div>

          <div v-else class="table-wrap">
            <VDataTable
              :headers="tableHeaders"
              :items="filteredTableItems"
              v-model:items-per-page="itemsPerPage"
              height="420"
              fixed-header
              class="text-no-wrap"
            >
              <template #item.name="{ item }">
                <span class="font-weight-medium">{{ item.nameText }}</span>
              </template>

              <template #item.notes="{ item }">
                <span class="text-medium-emphasis">{{ item.notesText }}</span>
              </template>

              <template #item.is_active="{ item }">
                <VChip
                  :color="statusColor(item.is_active)"
                  class="font-weight-medium"
                  size="small"
                  variant="tonal"
                >
                  {{ item.isActiveText }}
                </VChip>
              </template>

              <template #item.actions="{ item }">
                <div class="d-flex align-center">
                  <VBtn
                    v-if="canUpdateCategory"
                    icon
                    variant="text"
                    color="primary"
                    @click="openEditDialog(item)"
                  >
                    <VIcon icon="tabler-edit" />
                  </VBtn>

                  <VBtn
                    v-if="canDeleteCategory"
                    icon
                    variant="text"
                    color="error"
                    :loading="isDeleteLoading === item.id"
                    @click="deleteCategory(item.id)"
                  >
                    <VIcon icon="tabler-trash" />
                  </VBtn>
                </div>
              </template>

              <template #no-data>
                <div class="text-center py-6 text-medium-emphasis">
                  No expense categories found.
                </div>
              </template>
            </VDataTable>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <VDialog
    v-model="isCategoryDialogOpen"
    max-width="640"
    persistent
  >
    <VCard>
      <VCardItem>
        <VCardTitle>{{ isEditMode ? 'Edit Expense Category' : 'Add Expense Category' }}</VCardTitle>
        <template #append>
          <IconBtn @click="isCategoryDialogOpen = false">
            <VIcon icon="tabler-x" />
          </IconBtn>
        </template>
      </VCardItem>

      <VCardText>
        <VRow>
          <VCol cols="12">
            <AppTextField
              v-model="form.name"
              label="Name"
              placeholder="Category name"
              :error-messages="fieldErrors.name"
            />
          </VCol>

          <VCol cols="12">
            <AppTextField
              v-model="form.notes"
              label="Notes"
              placeholder="Optional notes"
              :error-messages="fieldErrors.notes"
            />
          </VCol>

          <VCol cols="12">
            <VSwitch
              v-model="form.is_active"
              label="Active"
              color="success"
              inset
            />
          </VCol>
        </VRow>
      </VCardText>

      <VCardActions class="justify-end px-6 pb-6">
        <VBtn
          variant="outlined"
          color="secondary"
          @click="isCategoryDialogOpen = false"
        >
          Cancel
        </VBtn>
        <VBtn
          color="primary"
          :loading="isSaving"
          @click="submitCategory"
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

.add-category-btn {
  border: 1px solid rgba(var(--v-theme-primary), 0.35);
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.16), rgba(var(--v-theme-primary), 0.32));
  box-shadow: 0 8px 20px rgba(var(--v-theme-primary), 0.22);
  transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
}

.add-category-btn:hover {
  box-shadow: 0 12px 28px rgba(var(--v-theme-primary), 0.3);
  filter: saturate(1.08);
  transform: translateY(-1px);
}
</style>
