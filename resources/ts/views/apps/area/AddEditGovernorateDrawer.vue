<script setup lang="ts">
import { integerValidator, requiredValidator } from '@/@core/utils/validators'
import { useApi } from '@/composables/useApi'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import type { VForm } from 'vuetify/components/VForm'

interface Props {
  isDrawerOpen: boolean
  governorate?: any
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
const followUpHours = ref(24)
const defaultShipperId = ref<number | null>(null)
const cities = ref<string[]>([])
const newCityName = ref('')

// 👉 Fetching Shippers
const { data: shippersData } = await useApi<any>('/shippers')

const shippers = computed(() => {
  const rawData = shippersData.value?.data || shippersData.value || []
  return rawData.map((s: any) => ({
    title: String(s.user?.name || s.name || 'Unknown'),
    value: Number(s.user_id || s.id)
  }))
})

watch(() => props.isDrawerOpen, (isOpen) => {
  if (isOpen) {
    if (props.governorate) {
      id.value = props.governorate.id
      name.value = props.governorate.name
      followUpHours.value = props.governorate.follow_up_hours
      
      // Ensure it's a number
      const shipperId = props.governorate.default_shipper_user_id
      defaultShipperId.value = shipperId ? Number(shipperId) : null
      
      cities.value = props.governorate.cities?.map((c: any) => c.name) || []
    } else {
      id.value = null
      name.value = ''
      followUpHours.value = 24
      defaultShipperId.value = null
      cities.value = []
    }
  }
})

watch(defaultShipperId, (newVal) => {
  // 
})

const addCity = () => {
  if (newCityName.value.trim()) {
    if (!cities.value.includes(newCityName.value.trim())) {
      cities.value.push(newCityName.value.trim())
    }
    newCityName.value = ''
  }
}

const removeCity = (index: number) => {
  cities.value.splice(index, 1)
}

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  refForm.value?.reset()
}

const onSubmit = async () => {
  const { valid } = await refForm.value!.validate()
  if (!valid) return

  const payload = {
    name: name.value,
    follow_up_hours: followUpHours.value,
    default_shipper_user_id: defaultShipperId.value,
    cities: cities.value,
  }
  

  const method = id.value ? 'PATCH' : 'POST'
  const url = id.value ? `/governorates/${id.value}` : '/governorates'
  

  try {
    const response = await $api(url, {
      method,
      body: payload,
    })
    
    alert('Area saved successfully!')
    emit('submit')
    emit('update:isDrawerOpen', false)
  } catch (error) {
    alert('Error saving area. Check console for details.')
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
    <AppDrawerHeaderSection
      :title="id ? 'Edit Area' : 'Add Area'"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="name"
                  label="Area Name"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="followUpHours"
                  type="number"
                  label="Follow-up Hours"
                  :rules="[requiredValidator, integerValidator]"
                />
              </VCol>

              <VCol cols="12">
                <AppSelect
                  v-model="defaultShipperId"
                  label="Default Shipper"
                  :items="shippers"
                  item-title="title"
                  item-value="value"
                  :return-object="false"
                  clearable
                />
              </VCol>

              <VCol cols="12">
                <div class="d-flex align-end gap-2">
                  <AppTextField
                    v-model="newCityName"
                    label="Add City"
                    placeholder="Enter city name"
                    @keyup.enter="addCity"
                  />
                  <VBtn
                    icon="tabler-plus"
                    variant="tonal"
                    style="margin-bottom: 2px;"
                    @click="addCity"
                  />
                </div>
              </VCol>

              <VCol cols="12">
                <div class="d-flex flex-wrap gap-1">
                  <VChip
                    v-for="(city, index) in cities"
                    :key="index"
                    closable
                    size="small"
                    @click:close="removeCity(index)"
                  >
                    {{ city }}
                  </VChip>
                </div>
              </VCol>

              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  {{ id ? 'Save Changes' : 'Submit' }}
                </VBtn>
                <VBtn
                  variant="tonal"
                  color="error"
                  @click="closeNavigationDrawer"
                >
                  Cancel
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
