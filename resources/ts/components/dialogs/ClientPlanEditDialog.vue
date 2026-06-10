<script setup lang="ts">
interface Props {
  isDialogVisible: boolean
  planId: number | undefined
  address: string | undefined
}

interface Emit {
  (e: 'update:isDialogVisible', val: boolean): void
  (e: 'submit', value: { plan_id: number; address: string }): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()

const formData = ref({
  plan_id: props.planId,
  address: props.address,
})

watch(() => props.isDialogVisible, (val) => {
  if (val) {
    formData.value = {
      plan_id: props.planId,
      address: props.address,
    }
  }
})

const plans = ref<any[]>([])

const fetchPlans = async () => {
  try {
    const res = await $api('/orders/init')
    plans.value = res.metadata?.plans || []
  } catch (e) {
    console.error(e)
  }
}

onMounted(fetchPlans)

const onFormSubmit = () => {
  if (formData.value.plan_id) {
    emit('submit', { 
      plan_id: formData.value.plan_id as number, 
      address: formData.value.address || '' 
    })
  }
}

const dialogModelValueUpdate = (val: boolean) => {
  emit('update:isDialogVisible', val)
}
</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 500"
    :model-value="props.isDialogVisible"
    @update:model-value="dialogModelValueUpdate"
  >
    <DialogCloseBtn @click="dialogModelValueUpdate(false)" />

    <VCard class="pa-sm-8 pa-2">
      <VCardText>
        <h4 class="text-h4 text-center mb-2">
          Change Shipping Plan
        </h4>
        <p class="text-body-1 text-center mb-6">
          Update the client's shipping plan and primary address.
        </p>

        <VForm
          class="mt-6"
          @submit.prevent="onFormSubmit"
        >
          <VRow>
            <VCol cols="12">
              <AppSelect
                v-model="formData.plan_id"
                label="Select Plan"
                :items="plans"
                item-title="name"
                item-value="id"
                placeholder="Choose Shipping Plan"
              />
            </VCol>

            <VCol cols="12">
              <AppTextField
                v-model="formData.address"
                label="Detailed Address"
                placeholder="City, Street, Apartment..."
              />
            </VCol>

            <VCol
              cols="12"
              class="d-flex flex-wrap justify-center gap-4 mt-4"
            >
              <VBtn type="submit">
                Save Changes
              </VBtn>

              <VBtn
                color="secondary"
                variant="tonal"
                @click="dialogModelValueUpdate(false)"
              >
                Cancel
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
