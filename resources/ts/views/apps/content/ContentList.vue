<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import AddEditContentDrawer from '@/views/apps/content/AddEditContentDrawer.vue'
import { createUrl } from '@core/composable/createUrl'

// Headers
const headers = [
  { title: 'Content Name', key: 'name' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const searchQuery = ref('')
const { can } = useAbility()

const STORAGE_KEY = 'contents-visible-columns'

// Define permission mapping for columns
const columnPermissions: Record<string, string> = {
  name: 'content.column.name.view',
}

const visibleHeaderKeys = ref(JSON.parse(localStorage.getItem(STORAGE_KEY) || JSON.stringify(headers.map(h => h.key))))

const activeHeaders = computed(() => {
  return headers.filter(h => {
    // 1. Check user manual visibility
    if (!visibleHeaderKeys.value.includes(h.key)) return false
    
    // 2. Check permission
    const perm = columnPermissions[h.key]
    if (perm && !can(perm as any, 'all' as any)) return false
    
    return true
  })
})

const filteredHeadersForMenu = computed(() => {
  return headers.filter(h => {
    const perm = columnPermissions[h.key]
    return !perm || can(perm as any, 'all' as any)
  })
})

watch(visibleHeaderKeys, (newVal) => {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal))
})

// 👉 Fetching Contents
const { data: contentsData, execute: fetchContents } = await useApi<any>(createUrl('/contents', {
  query: {
    q: searchQuery,
  },
}))

const contents = computed(() => contentsData.value || [])

const isAddEditDrawerVisible = ref(false)
const selectedContent = ref<any>(null)

const openAddDrawer = () => {
  selectedContent.value = null
  isAddEditDrawerVisible.value = true
}

const openEditDrawer = (content: any) => {
  selectedContent.value = content
  isAddEditDrawerVisible.value = true
}

const deleteContent = async (id: number) => {
  if (confirm('Are you sure you want to delete this content?')) {
    await $api(`/contents/${id}`, { method: 'DELETE' })
    fetchContents()
  }
}

const handleFormSubmit = () => {
  fetchContents()
}
</script>

<template>
  <section>
    <VCard>
      <!-- 👉 Search & Adds -->
      <VCardText class="d-flex flex-wrap gap-4">
        <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
          <div style="inline-size: 15.625rem;">
            <AppTextField
              v-model="searchQuery"
              placeholder="Search Content"
            />
          </div>

          <VBtn
            v-if="can('content.create' as any, 'all' as any)"
            prepend-icon="tabler-plus"
            @click="openAddDrawer"
          >
            Add Content
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

      <VDataTable
        :items="contents"
        :headers="activeHeaders"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <!-- Content Name -->
        <template #item.name="{ item }: { item: any }">
          <span class="text-h6 font-weight-medium">{{ item.name }}</span>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }: { item: any }">
          <div class="d-flex align-center gap-2">
            <IconBtn v-if="can('content.update' as any, 'all' as any)" size="small" @click="openEditDrawer(item)">
              <VIcon icon="tabler-edit" />
              <VTooltip activator="parent">Edit Content</VTooltip>
            </IconBtn>
            <IconBtn v-if="can('content.delete' as any, 'all' as any)" size="small" @click="deleteContent(item.id)">
              <VIcon icon="tabler-trash" />
              <VTooltip activator="parent">Delete Content</VTooltip>
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <AddEditContentDrawer
      v-model:isDrawerOpen="isAddEditDrawerVisible"
      :content="selectedContent"
      @submit="handleFormSubmit"
    />
  </section>
</template>
