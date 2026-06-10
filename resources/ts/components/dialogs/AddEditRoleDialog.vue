<script setup lang="ts">
import { computed, ref, watch } from 'vue'

interface Props {
  rolePermissions?: {
    id?: number
    name: string
    permissions: any[]
  }
  isDialogVisible: boolean
}

interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
}

const props = withDefaults(defineProps<Props>(), {
  rolePermissions: () => ({
    id: undefined,
    name: '',
    permissions: [],
  }),
})

interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'update:rolePermissions', value: any): void
}

const emit = defineEmits<Emit>()

// 👉 Roles & Permissions
const roleName = ref('')
const selectedPermissions = ref<string[]>([])
const allPermissions = ref<any[]>([])
const searchQuery = ref('')

const fetchPermissions = async () => {
  try {
    const res = await $api('/permissions')
    const rawPerms = res.data?.data || res.data || (Array.isArray(res) ? res : [])
    allPermissions.value = rawPerms
  } catch (e) {
    console.error('Fetch permissions error:', e)
  }
}

watch(() => props.isDialogVisible, (val) => {
  if (val) {
    fetchPermissions()
    // Always sync roleName and permissions when dialog opens
    roleName.value = props.rolePermissions?.name || ''
    selectedPermissions.value = (props.rolePermissions?.permissions || []).map(p => typeof p === 'string' ? p : p.name)
  } else {
    // Clear state on close
    roleName.value = ''
    selectedPermissions.value = []
  }
})

// 👉 Grouping logic
const groupedPermissions = computed(() => {
  const groups: Record<string, any[]> = {}
  
  const filtered = allPermissions.value.filter(p => 
    p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    (p.label && p.label.toLowerCase().includes(searchQuery.value.toLowerCase()))
  )

  filtered.forEach(perm => {
    // Group by explicit group from DB, fallback to name prefix
    const groupName = perm.group || perm.name.split('.')[0]
    if (!groups[groupName]) groups[groupName] = []
    groups[groupName].push(perm)
  })
  
  return groups
})

const toggleSelectAllGroup = (groupName: string, checked: boolean) => {
  const groupPerms = groupedPermissions.value[groupName].map(p => p.name)
  if (checked) {
    selectedPermissions.value = [...new Set([...selectedPermissions.value, ...groupPerms])]
  } else {
    selectedPermissions.value = selectedPermissions.value.filter(p => !groupPerms.includes(p))
  }
}

const isGroupAllSelected = (groupName: string) => {
  const groupPerms = groupedPermissions.value[groupName].map(p => p.name)
  return groupPerms.length > 0 && groupPerms.every(p => selectedPermissions.value.includes(p))
}

const isGroupIndeterminate = (groupName: string) => {
  const groupPerms = groupedPermissions.value[groupName].map(p => p.name)
  const selectedInGroup = groupPerms.filter(p => selectedPermissions.value.includes(p))
  return selectedInGroup.length > 0 && selectedInGroup.length < groupPerms.length
}

const toggleSelectAllPermissions = (checked: boolean) => {
  if (checked) {
    selectedPermissions.value = allPermissions.value.map(p => p.name)
  } else {
    selectedPermissions.value = []
  }
}

const isAllPermissionsSelected = computed(() => {
  return allPermissions.value.length > 0 && 
         allPermissions.value.every(p => selectedPermissions.value.includes(p.name))
})

const isAllPermissionsIndeterminate = computed(() => {
  return selectedPermissions.value.length > 0 && 
         selectedPermissions.value.length < allPermissions.value.length
})

const onSubmit = async () => {
  if (!roleName.value) {
    alert('Please enter a role name')
    return
  }

  try {
    const payload = {
      name: roleName.value,
      permissions: selectedPermissions.value,
    }

    if (props.rolePermissions?.id) {
      await $api(`/roles/${props.rolePermissions.id}`, {
        method: 'PUT',
        body: payload,
      })
      alert('Role updated successfully!')
    } else {
      await $api('/roles', {
        method: 'POST',
        body: payload,
      })
      alert('Role created successfully!')
    }
    
    emit('update:rolePermissions', { name: '', permissions: [] })
    emit('update:isDialogVisible', false)
  } catch (e: any) {
    console.error('Submit error:', e)
    const errorMsg = e.response?._data?.message || e.message || 'An error occurred'
    alert(`Failed to save role: ${errorMsg}`)
  }
}

const onReset = () => {
  emit('update:rolePermissions', { name: '', permissions: [] })
  emit('update:isDialogVisible', false)
  searchQuery.value = ''
}
</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 900"
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard class="pa-sm-10 pa-2">
      <VCardText>
        <h4 class="text-h4 text-center mb-2">
          {{ props.rolePermissions.name ? 'Edit' : 'Add New' }} Role
        </h4>
        <p class="text-body-1 text-center mb-6">
          Selected {{ selectedPermissions.length }} of {{ allPermissions.length }} Permissions
        </p>

        <VForm @submit.prevent="onSubmit">
          <AppTextField
            v-model="roleName"
            label="Role Name"
            placeholder="Enter Role Name"
            class="mb-6"
          />

          <div class="d-flex align-center justify-space-between mb-4">
            <h5 class="text-h5 d-flex align-center gap-2">
              <VCheckbox
                :model-value="isAllPermissionsSelected"
                :indeterminate="isAllPermissionsIndeterminate"
                @update:model-value="val => toggleSelectAllPermissions(!!val)"
              />
              Role Permissions
            </h5>
            <AppTextField
              v-model="searchQuery"
              placeholder="Search Permissions..."
              append-inner-icon="tabler-search"
              density="compact"
              style="max-width: 300px;"
            />
          </div>

          <VDivider class="mb-4" />

          <div class="permissions-container" style="max-height: 500px; overflow-y: auto;">
            <VExpansionPanels variant="accordion">
              <VExpansionPanel
                v-for="(groupPerms, group) in groupedPermissions"
                :key="group"
              >
                <VExpansionPanelTitle>
                  <div class="d-flex align-center w-100 gap-2">
                    <VCheckbox
                      :model-value="isGroupAllSelected(group)"
                      :indeterminate="isGroupIndeterminate(group)"
                      @update:model-value="val => toggleSelectAllGroup(group, !!val)"
                      @click.stop
                    />
                    <span class="text-capitalize font-weight-bold">{{ group }}</span>
                    <VChip size="x-small" density="compact" class="ml-2">
                      {{ groupPerms.filter(p => selectedPermissions.includes(p.name)).length }}/{{ groupPerms.length }}
                    </VChip>
                  </div>
                </VExpansionPanelTitle>
                <VExpansionPanelText>
                  <VRow>
                    <VCol
                      v-for="perm in groupPerms"
                      :key="perm.name"
                      cols="12"
                      sm="6"
                      md="4"
                    >
                      <VCheckbox
                        v-model="selectedPermissions"
                        :value="perm.name"
                        :label="perm.label || perm.name"
                        density="compact"
                        hide-details
                      />
                    </VCol>
                  </VRow>
                </VExpansionPanelText>
              </VExpansionPanel>
            </VExpansionPanels>
            
            <div v-if="Object.keys(groupedPermissions).length === 0" class="text-center pa-10 text-muted">
              No permissions found matching search.
            </div>
          </div>

          <div class="d-flex align-center justify-center gap-4 mt-8">
            <VBtn @click="onSubmit">Submit</VBtn>
            <VBtn color="secondary" variant="tonal" @click="onReset">Cancel</VBtn>
          </div>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss" scoped>
.permissions-container {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 8px;
  padding: 8px;
}
.v-expansion-panel-text__wrapper {
  padding: 16px !important;
}
</style>
