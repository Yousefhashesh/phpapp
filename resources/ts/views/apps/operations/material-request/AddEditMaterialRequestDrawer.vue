<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import type { VForm } from 'vuetify/components/VForm'
import { requiredValidator } from '@/@core/utils/validators'

interface Props {
  isDrawerOpen: boolean
  request?: any
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
const clientId = ref<number | null>(null)
const shipperId = ref<number | null>(null)
const deliveryType = ref('DELIVERY')
const combinedVisit = ref(false)
const status = ref('PENDING')
const approvalStatus = ref('PENDING')
const shippingCost = ref(0)
const items = ref<any[]>([])

// 👉 Fetching Data
const { data: clientsData } = await useApi<any>('/users?role=client')
const clients = computed(() => clientsData.value?.data || [])

const { data: shippersData } = await useApi<any>('/users?role=shipper')
const shippers = computed(() => shippersData.value?.data || [])

const { data: materialsData } = await useApi<any>('/materials')
const materials = computed(() => materialsData.value || [])

watch(() => props.isDrawerOpen, (isOpen) => {
  if (isOpen) {
    if (props.request) {
      id.value = props.request.id
      clientId.value = props.request.client_id
      shipperId.value = props.request.shipper_id
      deliveryType.value = props.request.delivery_type || 'DELIVERY'
      combinedVisit.value = !!props.request.combined_visit
      status.value = props.request.status || 'PENDING'
      approvalStatus.value = props.request.approval_status || 'PENDING'
      shippingCost.value = props.request.shipping_cost || 0
      items.value = props.request.items?.map((i: any) => ({
        material_id: i.material_id,
        quantity: i.quantity,
        price: i.price,
      })) || []
    } else {
      id.value = null
      clientId.value = null
      shipperId.value = null
      deliveryType.value = 'DELIVERY'
      combinedVisit.value = false
      status.value = 'PENDING'
      approvalStatus.value = 'PENDING'
      shippingCost.value = 0
      items.value = []
    }
  }
})

const addItemRow = () => {
  items.value.push({ material_id: null, quantity: 1, price: 0 })
}

const removeItemRow = (index: number) => {
  items.value.splice(index, 1)
}

const handleMaterialChange = (index: number, materialId: number) => {
  const mat = materials.value.find((m: any) => m.id === materialId)
  if (mat) {
    items.value[index].price = mat.sale_price
  }
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
    client_id: clientId.value,
    shipper_id: shipperId.value,
    delivery_type: deliveryType.value,
    combined_visit: combinedVisit.value,
    status: status.value,
    approval_status: approvalStatus.value,
    shipping_cost: shippingCost.value,
    items: items.value.filter(i => i.material_id),
  }

  // Calculate materials_total
  const materialsTotal = items.value.reduce((acc, i) => acc + (i.quantity * i.price), 0)
  Object.assign(payload, { materials_total: materialsTotal })

  const method = id.value ? 'PATCH' : 'POST'
  const url = id.value ? `/material-requests/${id.value}` : '/material-requests'

  try {
    await $api(url, {
      method,
      body: payload,
    })
    
    alert(`Material request ${id.value ? 'updated' : 'created'} successfully!`)
    emit('submit')
    closeNavigationDrawer()
  } catch (error) {
    console.error('Error saving material request:', error)
    alert('Error saving material request.')
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
      :title="id ? 'Update Material Request' : 'Add Material Request'"
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
                <AppSelect
                  v-model="clientId"
                  label="Client"
                  placeholder="Select Client"
                  :items="clients"
                  item-title="name"
                  item-value="id"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12">
                <AppSelect
                  v-model="shipperId"
                  label="Assigned Shipper"
                  placeholder="Select Shipper"
                  :items="shippers"
                  item-title="name"
                  item-value="id"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="deliveryType"
                  label="Delivery Type"
                  :items="['DELIVERY', 'PICKUP']"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="shippingCost"
                  type="number"
                  label="Shipping Cost (EGP)"
                  :disabled="deliveryType === 'PICKUP'"
                />
              </VCol>

              <VCol cols="12">
                <VSwitch
                  v-model="combinedVisit"
                  label="Combined with Pickup Visit"
                  :disabled="deliveryType === 'PICKUP'"
                />
              </VCol>

              <VCol cols="12">
                <AppSelect
                  v-model="status"
                  label="Execution Status"
                  :items="['PENDING', 'PROCESSING', 'COMPLETED', 'CANCELLED']"
                />
              </VCol>

              <VCol cols="12">
                <AppSelect
                  v-model="approvalStatus"
                  label="Approval Status"
                  :items="['PENDING', 'APPROVED', 'REJECTED']"
                />
              </VCol>

              <!-- Items Section -->
              <VCol cols="12">
                <div class="d-flex align-center justify-space-between mb-4">
                  <h6 class="text-h6">Requested Materials</h6>
                  <VBtn size="small" prepend-icon="tabler-plus" @click="addItemRow">
                    Add Item
                  </VBtn>
                </div>

                <div v-for="(item, index) in items" :key="index" class="d-flex align-center gap-2 mb-3">
                  <div style="flex: 2;">
                    <AppSelect
                      v-model="item.material_id"
                      placeholder="Material"
                      :items="materials"
                      item-title="name"
                      item-value="id"
                      density="compact"
                      hide-details
                      @update:model-value="(val) => handleMaterialChange(index, val)"
                    />
                  </div>
                  <div style="flex: 1;">
                    <AppTextField
                      v-model="item.quantity"
                      type="number"
                      placeholder="Qty"
                      density="compact"
                      hide-details
                    />
                  </div>
                  <div style="flex: 1;">
                    <AppTextField
                      v-model="item.price"
                      type="number"
                      placeholder="Price"
                      density="compact"
                      hide-details
                    />
                  </div>
                  <IconBtn color="error" size="small" @click="removeItemRow(index)">
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
