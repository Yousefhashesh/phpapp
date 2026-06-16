<script setup lang="ts">
interface Props {
  page: number
  itemsPerPage: number
  totalItems: number
}

interface Emit {
  (e: 'update:page', value: number): void
  (e: 'update:itemsPerPage', value: number): void
}

const props = defineProps<Props>()

const emit = defineEmits<Emit>()

const STORAGE_KEY = 'orders-items-per-page'

// Rows per page options
const rowsPerPageOptions = [25, 50, 100]

const updatePage = (value: number) => {
  emit('update:page', value)
}

const updateItemsPerPage = (value: number) => {
  // Save to localStorage
  localStorage.setItem(STORAGE_KEY, String(value))
  emit('update:itemsPerPage', value)
}
</script>

<template>
  <div>
    <VDivider />

    <div class="d-flex align-center justify-sm-space-between justify-center flex-wrap gap-3 px-6 py-3">
      <div class="d-flex align-center gap-4 flex-wrap">
        <p class="text-disabled mb-0">
          {{ paginationMeta({ page: props.page, itemsPerPage: props.itemsPerPage }, totalItems) }}
        </p>

        <!--    Rows Per Page Selector -->
        <div class="d-flex align-center gap-2">
          <span class="text-disabled text-xs">Show</span>
          <VSelect
            :model-value="props.itemsPerPage"
            :items="rowsPerPageOptions"
            density="compact"
            variant="outlined"
            style="max-inline-size: 80px;"
            hide-details
            class="rows-per-page-select"
            @update:model-value="updateItemsPerPage"
          />
          <span class="text-disabled text-xs">entries</span>
        </div>
      </div>

      <VPagination
        :model-value="props.page"
        active-color="primary"
        :length="Math.ceil(totalItems / props.itemsPerPage)"
        :total-visible="$vuetify.display.xs ? 1 : Math.min(Math.ceil(totalItems / props.itemsPerPage), 5)"
        @update:model-value="updatePage"
      />
    </div>
  </div>
</template>

<style lang="scss" scoped>
.rows-per-page-select {
  :deep(.v-field__input) {
    font-size: 0.875rem !important;
    padding-block: 4px !important;
  }
}
</style>
