<script setup lang="ts">
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import type { VForm } from 'vuetify/components/VForm'
import { requiredValidator } from '@/@core/utils/validators'

interface Props {
  isDrawerOpen: boolean
  material?: any
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
const code = ref('')
const costPrice = ref(0)
const salePrice = ref(0)
const stock = ref(0)
const isActive = ref(true)
const notes = ref('')

watch(() => props.isDrawerOpen, (isOpen) => {
  if (isOpen) {
    if (props.material) {
      id.value = props.material.id
      name.value = props.material.name
      code.value = props.material.code || ''
      costPrice.value = props.material.cost_price || 0
      salePrice.value = props.material.sale_price || 0
      stock.value = props.material.stock || 0
      isActive.value = !!props.material.is_active
      notes.value = props.material.notes || ''
    } else {
      id.value = null
      name.value = ''
      code.value = ''
      costPrice.value = 0
      salePrice.value = 0
      stock.value = 0
      isActive.value = true
      notes.value = ''
    }
  }
})

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
    code: code.value,
    cost_price: costPrice.value,
    sale_price: salePrice.value,
    stock: stock.value,
    is_active: isActive.value,
    notes: notes.value,
  }

  const method = id.value ? 'PATCH' : 'POST'
  const url = id.value ? `/materials/${id.value}` : '/materials'

  try {
    await $api(url, {
      method,
      body: payload,
    })
    
    alert(`Material ${id.value ? 'updated' : 'created'} successfully!`)
    emit('submit')
    closeNavigationDrawer()
  } catch (error) {
    console.error('Error saving material:', error)
    alert('Error saving material.')
  }
}

const handleDrawerModelValueUpdate = (val: boolean) => {
  emit('update:isDrawerOpen', val)
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Header -->
    <AppDrawerHeaderSection
      :title="id ? 'Update Material' : 'Add Material'"
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
                  label="Material Name"
                  placeholder="Bubble Wrap"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="code"
                  label="SKU/Code"
                  placeholder="BW-001"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="costPrice"
                  type="number"
                  label="Cost Price"
                  prefix="EGP"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="salePrice"
                  type="number"
                  label="Sale Price"
                  prefix="EGP"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="stock"
                  type="number"
                  label="Current Stock"
                />
              </VCol>

              <VCol cols="12">
                <VSwitch
                  v-model="isActive"
                  label="Is Active"
                />
              </VCol>

              <VCol cols="12">
                <AppTextarea
                  v-model="notes"
                  label="Notes"
                  placeholder="Optional material details..."
                />
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
