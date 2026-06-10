<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import type { VForm } from 'vuetify/components/VForm'
import { requiredValidator } from '@/@core/utils/validators'

interface Props {
  isDrawerOpen: boolean
  content?: any
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

watch(() => props.isDrawerOpen, (isOpen) => {
  if (isOpen) {
    if (props.content) {
      id.value = props.content.id
      name.value = props.content.name
    } else {
      id.value = null
      name.value = ''
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
  }

  const method = id.value ? 'PATCH' : 'POST'
  const url = id.value ? `/contents/${id.value}` : '/contents'

  try {
    await $api(url, {
      method,
      body: payload,
    })
    
    alert(`Content ${id.value ? 'updated' : 'created'} successfully!`)
    emit('submit')
    closeNavigationDrawer()
  } catch (error) {
    console.error('Error saving content:', error)
    alert('Error saving content. Please check console.')
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
      :title="id ? 'Update Content' : 'Add Content'"
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
                  label="Content Name"
                  placeholder="Electronics"
                  :rules="[requiredValidator]"
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
