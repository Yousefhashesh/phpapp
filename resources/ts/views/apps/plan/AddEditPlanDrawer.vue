<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import type { VForm } from 'vuetify/components/VForm'
import { requiredValidator, integerValidator } from '@/@core/utils/validators'

interface Props {
  isDrawerOpen: boolean
  plan?: any
}

interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'submit'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()

const isFormValid = ref(false)
const refForm = ref<VForm>()

// Form data
const id = ref<number | null>(null)
const name = ref('')
const orderCount = ref(0)
const prices = ref<any[]>([])

// 👉 Fetching Governorates for selection
const { data: governoratesData } = await useApi<any>('/governorates')
const governorates = computed(() => governoratesData.value || [])

watch(() => props.isDrawerOpen, (isOpen) => {
  if (isOpen) {
    if (props.plan) {
      id.value = props.plan.id
      name.value = props.plan.name
      orderCount.value = props.plan.order_count
      prices.value = props.plan.prices?.map((p: any) => ({
        governorate_id: p.governorate_id,
        price: p.price,
      })) || []
    } else {
      id.value = null
      name.value = ''
      orderCount.value = 0
      prices.value = []
    }
  }
})

const addPriceRow = () => {
  prices.value.push({ governorate_id: null, price: 0 })
}

const removePriceRow = (index: number) => {
  prices.value.splice(index, 1)
}

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })
}

const onSubmit = async () => {
  const { valid } = await refForm.value!.validate()
  if (!valid) return

  const payload = {
    name: name.value,
    order_count: orderCount.value,
    prices: prices.value.filter(p => p.governorate_id !== null),
  }

  const method = id.value ? 'PATCH' : 'POST'
  const url = id.value ? `/plans/${id.value}` : '/plans'

  try {
    await $api(url, {
      method,
      body: payload,
    })
    
    alert(`Plan ${id.value ? 'updated' : 'created'} successfully!`)
    emit('submit')
    closeNavigationDrawer()
  } catch (error) {
    console.error('Error saving plan:', error)
    alert('Error saving plan. Check console.')
  }
}

const handleDrawerModelValueUpdate = (val: boolean) => {
  emit('update:isDrawerOpen', val)
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="500"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Header -->
    <AppDrawerHeaderSection
      :title="id ? 'Update Plan' : 'Add Plan'"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <!-- 👉 Form -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="name"
                  label="Plan Name"
                  placeholder="Standard Plan"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="orderCount"
                  type="number"
                  label="Order Count Cap"
                  placeholder="100"
                  :rules="[requiredValidator, integerValidator]"
                />
              </VCol>

              <VCol cols="12">
                <div class="d-flex align-center justify-space-between mb-4">
                  <h6 class="text-h6">Pricing per Area</h6>
                  <VBtn
                    size="small"
                    prepend-icon="tabler-plus"
                    @click="addPriceRow"
                  >
                    Add Price
                  </VBtn>
                </div>

                <div v-if="prices.length === 0" class="text-center text-muted mb-4">
                  No prices defined yet.
                </div>

                <div v-for="(priceRow, index) in prices" :key="index" class="d-flex align-center gap-2 mb-3">
                  <div style="flex: 2;">
                    <AppSelect
                      v-model="priceRow.governorate_id"
                      placeholder="Select Area"
                      :items="governorates"
                      item-title="name"
                      item-value="id"
                      density="compact"
                      hide-details
                    />
                  </div>
                  <div style="flex: 1;">
                    <AppTextField
                      v-model="priceRow.price"
                      type="number"
                      placeholder="Price"
                      density="compact"
                      hide-details
                    />
                  </div>
                  <IconBtn color="error" size="small" @click="removePriceRow(index)">
                    <VIcon icon="tabler-trash" />
                  </IconBtn>
                </div>
              </VCol>

              <VCol cols="12">
                <div class="d-flex gap-4">
                  <VBtn type="submit">
                    {{ id ? 'Update' : 'Submit' }}
                  </VBtn>
                  <VBtn
                    color="secondary"
                    variant="tonal"
                    @click="closeNavigationDrawer"
                  >
                    Cancel
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
