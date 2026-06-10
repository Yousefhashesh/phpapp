<script setup lang="ts">
interface Props {
  isDialogVisible: boolean
  commissionRate: string | number | undefined
}

interface Emit {
  (e: 'update:isDialogVisible', val: boolean): void
  (e: 'submit', value: { commission_rate: number }): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()

const formData = ref({
  commission_rate: props.commissionRate ? Number(props.commissionRate) : 0,
})

watch(() => props.isDialogVisible, (val) => {
  if (val) {
    formData.value = {
      commission_rate: props.commissionRate ? Number(props.commissionRate) : 0,
    }
  }
})

const onFormSubmit = () => {
  if (formData.value.commission_rate >= 0) {
    emit('submit', { commission_rate: formData.value.commission_rate as number })
  }
}

const dialogModelValueUpdate = (val: boolean) => {
  emit('update:isDialogVisible', val)
}
</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 400"
    :model-value="props.isDialogVisible"
    @update:model-value="dialogModelValueUpdate"
  >
    <DialogCloseBtn @click="dialogModelValueUpdate(false)" />

    <VCard class="pa-sm-8 pa-2">
      <VCardText>
        <h4 class="text-h4 text-center mb-2">
          Update Commission
        </h4>
        <p class="text-body-1 text-center mb-6">
          Change the fixed commission amount per successful order.
        </p>

        <VForm
          class="mt-6"
          @submit.prevent="onFormSubmit"
        >
          <VRow>
            <VCol cols="12">
              <AppTextField
                v-model="formData.commission_rate"
                label="Commission Rate (EGP)"
                type="number"
                placeholder="50"
                hint="Fixed amount paid for each delivery."
                persistent-hint
              />
            </VCol>

            <VCol
              cols="12"
              class="d-flex flex-wrap justify-center gap-4 mt-6"
            >
              <VBtn type="submit">
                Save Rate
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
