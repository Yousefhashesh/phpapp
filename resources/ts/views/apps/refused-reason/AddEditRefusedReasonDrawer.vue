<script setup lang="ts">
import { requiredValidator } from '@/@core/utils/validators'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import type { VForm } from 'vuetify/components/VForm'

interface Props {
  isDrawerOpen: boolean
  refusedReason?: any
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
const reason = ref('')
const status = ref('UNDELIVERED')
const isActive = ref(true)
const isClear = ref(false)
const isEditAmount = ref(false)

const statusOptions = [
  { title: 'Out For Delivery', value: 'OUT_FOR_DELIVERY' },
  { title: 'Delivered', value: 'DELIVERED' },
  { title: 'Hold', value: 'HOLD' },
  { title: 'Undelivered', value: 'UNDELIVERED' },
]

watch(() => props.isDrawerOpen, (isOpen) => {
  if (isOpen) {
    if (props.refusedReason) {
      id.value = props.refusedReason.id
      reason.value = props.refusedReason.reason
      status.value = props.refusedReason.status
      isActive.value = !!props.refusedReason.is_active
      isClear.value = !!props.refusedReason.is_clear
      isEditAmount.value = !!props.refusedReason.is_edit_amount
    } else {
      id.value = null
      reason.value = ''
      status.value = 'UNDELIVERED'
      isActive.value = true
      isClear.value = false
      isEditAmount.value = false
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
    reason: reason.value,
    status: status.value,
    is_active: isActive.value,
    is_clear: isClear.value,
    is_edit_amount: isEditAmount.value,
  }

  const method = id.value ? 'PATCH' : 'POST'
  const url = id.value ? `/refused-reasons/${id.value}` : '/refused-reasons'

  try {
    await $api(url, {
      method,
      body: payload,
    })
    
    alert(`Refused reason ${id.value ? 'updated' : 'created'} successfully!`)
    emit('submit')
    closeNavigationDrawer()
  } catch (error) {
    console.error('Error saving refused reason:', error)
    alert('Error saving refused reason. Please check console.')
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
      :title="id ? 'Update Refused Reason' : 'Add Refused Reason'"
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
                  v-slot:item.reason
                  v-model="reason"
                  label="Reason"
                  placeholder="Customer rejected"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12">
                <AppSelect
                  v-model="status"
                  label="Status Map"
                  :items="statusOptions"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12">
                <VSwitch
                  v-model="isActive"
                  label="Active"
                  density="compact"
                />
              </VCol>

              <VCol cols="12">
                <VSwitch
                  v-model="isClear"
                  label="Clear"
                  density="compact"
                />
              </VCol>


              <VCol cols="12">
                <VSwitch
                  v-model="isEditAmount"
                  label="Can Edit Amount"
                  density="compact"
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
