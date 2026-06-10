<script setup lang="ts">
import { ref, watch, computed } from 'vue'

interface Props {
  user?: {
    id: number
    name: string
    roles: any[]
  }
  isDialogVisible: boolean
}

const props = withDefaults(defineProps<Props>(), {
  user: () => ({
    id: 0,
    name: '',
    roles: [],
  }),
})

interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'success'): void
}

const emit = defineEmits<Emit>()

const selectedRoles = ref<string[]>([])
const allRoles = ref<any[]>([])
const isLoading = ref(false)

const fetchRoles = async () => {
  try {
    const res = await $api('/roles')
    allRoles.value = res.data?.data || res.data || []
  } catch (e) {
    console.error('Fetch roles error:', e)
  }
}

watch(() => props.isDialogVisible, (val) => {
  if (val) {
    fetchRoles()
    selectedRoles.value = (props.user?.roles || []).map(r => r.name)
  }
})

const onSubmit = async () => {
  isLoading.value = true
  try {
    await $api(`/users/${props.user.id}/roles`, {
      method: 'PATCH',
      body: {
        roles: selectedRoles.value,
      },
    })
    
    alert('User roles updated successfully!')
    emit('success')
    emit('update:isDialogVisible', false)
  } catch (e: any) {
    console.error('Update roles error:', e)
    const errorMsg = e.response?._data?.message || e.message || 'An error occurred'
    alert(`Failed to update roles: ${errorMsg}`)
  } finally {
    isLoading.value = false
  }
}

const onReset = () => {
  emit('update:isDialogVisible', false)
}
</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 600"
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard class="pa-sm-10 pa-2">
      <VCardText>
        <h4 class="text-h4 text-center mb-2">Update User Roles</h4>
        <p class="text-body-1 text-center mb-6">Changing roles for: <strong>{{ props.user.name }}</strong></p>

        <VForm @submit.prevent="onSubmit">
          <VRow>
            <VCol cols="12">
              <AppSelect
                v-model="selectedRoles"
                label="Select Roles"
                placeholder="Select roles for this user"
                :items="allRoles.map(r => ({ title: r.label || r.name, value: r.name }))"
                multiple
                chips
                closable-chips
                clearable
                clear-icon="tabler-x"
              />
            </VCol>
          </VRow>

          <div class="d-flex align-center justify-center gap-4 mt-8">
            <VBtn :loading="isLoading" @click="onSubmit">Save Changes</VBtn>
            <VBtn color="secondary" variant="tonal" @click="onReset">Cancel</VBtn>
          </div>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
