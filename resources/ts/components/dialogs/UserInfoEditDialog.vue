<script setup lang="ts">
interface UserData {
  id: number | null
  name: string
  username: string
  phone: string | undefined
  account_type: number
  commission_rate: number | string | undefined
  plan_id: number | undefined
  address: string | undefined
}

interface Props {
  userData?: UserData
  isDialogVisible: boolean
}

interface Emit {
  (e: 'submit', value: any): void
  (e: 'update:isDialogVisible', val: boolean): void
}

const props = withDefaults(defineProps<Props>(), {
  userData: () => ({
    id: null,
    name: '',
    username: '',
    phone: '',
    account_type: 0,
    commission_rate: 0,
    plan_id: undefined,
    address: '',
  }),
})

const emit = defineEmits<Emit>()

const userData = ref<UserData>(structuredClone(toRaw(props.userData)))

watch(() => props.isDialogVisible, (val) => {
  if (val) userData.value = structuredClone(toRaw(props.userData))
})

const plans = ref<any[]>([])
const contents = ref<any[]>([])

const fetchMetadata = async () => {
  try {
    const res = await $api('/orders/init')
    plans.value = res.plans || []
    contents.value = res.contents || []
  } catch (e) {
    console.error(e)
  }
}

onMounted(() => {
  fetchMetadata()
})

const onFormSubmit = () => {
  const submitData = { ...userData.value }
  
  emit('submit', submitData)
}

const onFormReset = () => {
  userData.value = structuredClone(toRaw(props.userData))
  emit('update:isDialogVisible', false)
}

const dialogModelValueUpdate = (val: boolean) => {
  emit('update:isDialogVisible', val)
}
</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 800"
    :model-value="props.isDialogVisible"
    @update:model-value="dialogModelValueUpdate"
  >
    <DialogCloseBtn @click="dialogModelValueUpdate(false)" />

    <VCard class="pa-sm-10 pa-2">
      <VCardText>
        <h4 class="text-h4 text-center mb-2">
          Edit User Information
        </h4>
        <p class="text-body-1 text-center mb-6">
          Update the profile details and project-specific settings.
        </p>

        <VForm
          class="mt-6"
          @submit.prevent="onFormSubmit"
        >
          <VRow>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="userData.name"
                label="Full Name"
                placeholder="John Doe"
              />
            </VCol>

            <VCol cols="12" md="6">
              <AppTextField
                v-model="userData.username"
                label="Username"
                placeholder="johndoe"
              />
            </VCol>

            <VCol cols="12" md="6">
              <AppTextField
                v-model="userData.phone"
                label="Phone Number"
                placeholder="01xxxxxxxxx"
              />
            </VCol>

            
            <VCol v-if="userData.account_type === 1" cols="12">
              <AppTextField
                v-model="userData.address"
                label="Address"
                placeholder="City, Street..."
              />
            </VCol>

            <template v-else-if="userData.account_type === 2"> <!-- Shipper -->
              <VCol cols="12">
                <AppTextField
                  v-model="userData.commission_rate"
                  label="Commission Rate (EGP)"
                  type="number"
                  placeholder="50"
                />
              </VCol>
            </template>

            <VCol
              cols="12"
              class="d-flex flex-wrap justify-center gap-4"
            >
              <VBtn type="submit">
                Save Changes
              </VBtn>

              <VBtn
                color="secondary"
                variant="tonal"
                @click="onFormReset"
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
