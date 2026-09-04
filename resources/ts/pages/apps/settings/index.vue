<script setup lang="ts">
import { useNotificationStore } from '@/stores/useNotificationStore'

// const settingsData = ref<any>({})
const settingsData = ref<any>({
  site_identity: {
    site_name: '',
    site_email: '',
    site_phone: '',
    site_address: '',
    site_maintenance_mode: 'false',
  },

  working_hours: {
    working_hours_orders_start: '',
    working_hours_orders_end: '',
    working_hours_pickups_start: '',
    working_hours_pickups_end: '',
  },

  orders: {
    order_prefix: '',
    order_digits: '',
  },

  collections: {
    order_follow_up_hours: '',
  },

  plans: {
    welcome_plans: '',
  },

  financial_formulas: {
    formula_company_amount: '',
    formula_cod_amount: '',
    formula_shipper_collection_net_amount: '',
    formula_client_settlement_net_amount: '',
  },

  whatsapp: {
    whatsapp_enabled: 'false',
    whatsapp_replace_order_notifications: 'no',
    whatsapp_group_id: '',
    whatsapp_service_url: '',
    whatsapp_group_client_mapping: '[]',
  },

  site_theme: {
    site_color_primary_light: '#7367f0',
    site_color_primary_dark: '#7367f0',
  },

  social_media: {
    social_facebook: '',
    social_instagram: '',
    social_x: '',
    social_whatsapp: '',
  },
})

const activeTab = ref('site_identity')
const plansList = ref<any[]>([])
const notificationStore = useNotificationStore()

definePage({
  meta: {
    action: 'manage',
    subject: 'setting.page',
  },
})

// const fetchSettings = async () => {
//   const response = await $api('/settings')
//   settingsData.value = response
// }

const fetchSettings = async () => {
  try {
    const response = await $api('/settings')

    settingsData.value = {
      ...settingsData.value,

      site_identity: {
        ...settingsData.value.site_identity,
        ...(response.site_identity || {}),
      },

      working_hours: {
        ...settingsData.value.working_hours,
        ...(response.working_hours || {}),
      },

      orders: {
        ...settingsData.value.orders,
        ...(response.orders || {}),
      },

      collections: {
        ...settingsData.value.collections,
        ...(response.collections || {}),
      },

      plans: {
        ...settingsData.value.plans,
        ...(response.plans || {}),
      },

      financial_formulas: {
        ...settingsData.value.financial_formulas,
        ...(response.financial_formulas || {}),
      },

      whatsapp: {
        ...settingsData.value.whatsapp,
        ...(response.whatsapp || {}),
      },

      site_theme: {
        ...settingsData.value.site_theme,
        ...(response.site_theme || {}),
      },

      social_media: {
        ...settingsData.value.social_media,
        ...(response.social_media || {}),
      },
    }
  } catch (error) {
    console.error('Failed to fetch settings:', error)
  }
}

const fetchPlans = async () => {
  const response = await $api('/plans')
  plansList.value = response
}

const welcomePlansArray = computed({
  get: () => {
    const val = settingsData.value.plans?.welcome_plans
    if (!val) return []
    if (Array.isArray(val)) return val
    return val.split(',').map(Number)
  },
  set: (val: any[]) => {
    if (settingsData.value.plans) {
      settingsData.value.plans.welcome_plans = val.join(',')
    }
  },
})

const whatsappGroupsArray = computed({
  get: () => {
    const val = settingsData.value.whatsapp?.whatsapp_group_id
    if (!val) return []
    if (Array.isArray(val)) return val
    return val.split(',').map((s: any) => String(s).trim()).filter(Boolean)
  },
  set: (val: string[]) => {
    if (settingsData.value.whatsapp) {
      settingsData.value.whatsapp.whatsapp_group_id = val.join(',')
    }
  },
})

const updateSettings = async () => {
  const isFormWithFiles = logos.value.icon || logos.value.logoLight || logos.value.logoDark
  const flatSettings: any = {}
  Object.values(settingsData.value).forEach((group: any) => {
    Object.assign(flatSettings, group)
  })

  // Convert array to string if multiple selection used
  if (Array.isArray(flatSettings.welcome_plans)) {
    flatSettings.welcome_plans = flatSettings.welcome_plans.join(',')
  }

  try {
    if (isFormWithFiles) {
      const formData = new FormData()
      Object.entries(flatSettings).forEach(([key, val]) => {
        if (val !== null && val !== undefined) {
           formData.append(`settings[${key}]`, String(val))
        }
      })

      if (logos.value.icon) formData.append('site_logo_32_light', logos.value.icon)
      if (logos.value.logoLight) formData.append('site_logo_512_light', logos.value.logoLight)
      if (logos.value.logoDark) formData.append('site_logo_512_dark', logos.value.logoDark)

      await $api('/settings', {
        method: 'POST',
        headers: { 'X-HTTP-Method-Override': 'PUT' },
        body: formData,
      })
    } else {
      await $api('/settings', {
        method: 'PUT',
        body: { settings: flatSettings },
      })
    }
    notificationStore.success('تم تحديث الإعدادات بنجاح!')
    fetchSettings()
  } catch (error) {
    notificationStore.error('فشل في تحديث الإعدادات.')
  }
}

// Logo state for upload
const logos = ref({
  icon: null as File | null,
  logoLight: null as File | null,
  logoDark: null as File | null,
})

const onFileChange = (e: Event, key: 'icon' | 'logoLight' | 'logoDark') => {
  const files = (e.target as HTMLInputElement).files
  if (files?.length) {
    logos.value[key] = files[0]
  }
}

const whatsappStatus = ref<{
  ready: boolean
  has_qr?: boolean
  message?: string
  status?: string
  last_error?: string | null
  last_disconnect_reason?: string | null
  api_secret_configured?: boolean
  started_at?: string
} | null>(null)
const whatsappQr = ref<string | null>(null)
const whatsappGroups = ref<Array<{ id: string; name: string }>>([])
const whatsappGroupsLoading = ref(false)
const whatsappQrLoading = ref(false)
let whatsappPollTimer: ReturnType<typeof setInterval> | null = null

const clients = ref<any[]>([])
const fetchClients = async () => {
  try {
    const response = await $api('/clients?per_page=-1')
    const data = Array.isArray(response)
      ? response
      : (response && Array.isArray(response.data) ? response.data : [])
    clients.value = data.map((c: any) => ({
      id: c.user_id || c.id,
      name: c.user?.name || 'Unknown Client',
    }))
  } catch (error) {
    console.error('Failed to fetch clients:', error)
  }
}

const groupMappings = ref<Array<{ group_id: string; client_ids: number[] }>>([])

watch(() => settingsData.value.whatsapp?.whatsapp_group_client_mapping, (newVal) => {
  try {
    groupMappings.value = newVal ? JSON.parse(newVal) : []
  } catch {
    groupMappings.value = []
  }
}, { immediate: true })

watch(groupMappings, (newVal) => {
  if (settingsData.value.whatsapp) {
    settingsData.value.whatsapp.whatsapp_group_client_mapping = JSON.stringify(newVal)
  }
}, { deep: true })

const addGroupMapping = () => {
  groupMappings.value.push({ group_id: '', client_ids: [] })
}

const removeGroupMapping = (index: number) => {
  groupMappings.value.splice(index, 1)
}

const checkWhatsAppStatus = async () => {
  try {
    const response = await $api('/whatsapp/status')
    whatsappStatus.value = {
      ready: !!response.ready,
      has_qr: !!response.has_qr,
      message: response.message,
      status: response.status,
      last_error: response.last_error,
      last_disconnect_reason: response.last_disconnect_reason,
      api_secret_configured: response.api_secret_configured,
      started_at: response.started_at,
    }

    if (response.ready) {
      whatsappQr.value = null
      await fetchWhatsAppGroups()
    } else if (response.has_qr) {
      await fetchWhatsAppQr()
    }
  } catch {
    whatsappStatus.value = {
      ready: false,
      message: 'خدمة الواتساب غير متصلة. تأكد أن الخدمة شغّالة على السيرفر.',
    }
    whatsappQr.value = null
  }
}

const fetchWhatsAppQr = async () => {
  whatsappQrLoading.value = true
  try {
    const response = await $api('/whatsapp/qr')
    whatsappQr.value = response.qr || null
  } catch {
    whatsappQr.value = null
  } finally {
    whatsappQrLoading.value = false
  }
}

const fetchWhatsAppGroups = async () => {
  whatsappGroupsLoading.value = true
  try {
    const response = await $api('/whatsapp/groups')
    whatsappGroups.value = response.groups || []
  } catch {
    whatsappGroups.value = []
  } finally {
    whatsappGroupsLoading.value = false
  }
}

const restartWhatsApp = async () => {
  try {
    await $api('/whatsapp/restart', { method: 'POST' })
    whatsappQr.value = null
    await checkWhatsAppStatus()
  } catch {
    whatsappStatus.value = {
      ready: false,
      message: 'Could not restart WhatsApp service from dashboard.',
    }
  }
}

const startWhatsAppPolling = () => {
  stopWhatsAppPolling()
  whatsappPollTimer = setInterval(() => {
    if (activeTab.value === 'whatsapp' && !whatsappStatus.value?.ready) {
      checkWhatsAppStatus()
    }
  }, 5000)
}

const stopWhatsAppPolling = () => {
  if (whatsappPollTimer) {
    clearInterval(whatsappPollTimer)
    whatsappPollTimer = null
  }
}

const tabs = [
  { title: 'Identity & Branding', value: 'site_identity', icon: 'tabler-info-circle' },
  { title: 'Working Hours', value: 'working_hours', icon: 'tabler-clock' },
  { title: 'Orders & Plans', value: 'orders', icon: 'tabler-shopping-cart' },
  { title: 'Financial Formulas', value: 'financial_formulas', icon: 'tabler-calculator' },
  { title: 'WhatsApp Log', value: 'whatsapp', icon: 'tabler-brand-whatsapp' },
  { title: 'Site Color (Primary)', value: 'site_theme', icon: 'tabler-palette' },
  { title: 'Social Media', value: 'social_media', icon: 'tabler-brand-facebook' },
]

watch(activeTab, tab => {
  if (tab === 'whatsapp') {
    checkWhatsAppStatus()
    startWhatsAppPolling()
  } else {
    stopWhatsAppPolling()
  }
})

onMounted(() => {
  fetchSettings()
  fetchPlans()
  fetchClients()
  checkWhatsAppStatus()
})

onBeforeUnmount(() => {
  stopWhatsAppPolling()
})
</script>

<template>
  <VRow v-if="Object.keys(settingsData).length > 0">
    <VCol cols="12" md="3">
      <VTabs
        v-model="activeTab"
        direction="vertical"
        class="v-tabs-pill"
      >
        <VTab
          v-for="tab in tabs"
          :key="tab.value"
          :value="tab.value"
        >
          <VIcon
            start
            :icon="tab.icon"
          />
          {{ tab.title }}
        </VTab>
      </VTabs>
    </VCol>

    <VCol cols="12" md="9">
      <VCard>
        <VCardText>
          <VWindow v-model="activeTab">
            <!-- Site Identity -->
            <VWindowItem value="site_identity">
              <VRow>
                <VCol cols="12">
                  <h6 class="text-h6 mb-4">Site Information</h6>
                </VCol>
                <VCol cols="12" md="6">
                   <AppTextField v-model="settingsData.site_identity.site_name" label="Site Name" />
                </VCol>
                <VCol cols="12" md="6">
                   <AppTextField v-model="settingsData.site_identity.site_email" label="Site Email" />
                </VCol>
                <VCol cols="12" md="6">
                   <AppTextField v-model="settingsData.site_identity.site_phone" label="Site Phone" />
                </VCol>
                <VCol cols="12" md="6">
                   <AppTextField v-model="settingsData.site_identity.site_address" label="Site Address" />
                </VCol>
                
                <VCol cols="12">
                  <h6 class="text-h6 mt-4 mb-4">Site Branding (Logos)</h6>
                </VCol>
                <VCol cols="12" md="4">
                   <VFileInput label="Site Icon (32x32)" @change="onFileChange($event, 'icon')" density="compact" />
                </VCol>
                <VCol cols="12" md="4">
                   <VFileInput label="Light Logo (Black Text)" @change="onFileChange($event, 'logoLight')" density="compact" />
                </VCol>
                <VCol cols="12" md="4">
                   <VFileInput label="Dark Logo (White Text)" @change="onFileChange($event, 'logoDark')" density="compact" />
                </VCol>

                <VCol cols="12">
                  <VSwitch
                    v-model="settingsData.site_identity.site_maintenance_mode"
                    label="Maintenance Mode"
                    true-value="true"
                    false-value="false"
                  />
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- Working Hours -->
            <VWindowItem value="working_hours">
              <VRow>
                <VCol cols="12">
                  <h6 class="text-h6 mb-4">Order & Pickup Hours</h6>
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField 
                    v-model="settingsData.working_hours.working_hours_orders_start" 
                    label="Orders Start" 
                    type="time"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField 
                    v-model="settingsData.working_hours.working_hours_orders_end" 
                    label="Orders End" 
                    type="time" 
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField 
                    v-model="settingsData.working_hours.working_hours_pickups_start" 
                    label="Pickups Start" 
                    type="time"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField 
                    v-model="settingsData.working_hours.working_hours_pickups_end" 
                    label="Pickups End" 
                    type="time"
                  />
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- Orders & Plans -->
            <VWindowItem value="orders">
              <VRow>
                <VCol cols="12">
                  <h6 class="text-h6 mb-4">Orders Config</h6>
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="settingsData.orders.order_prefix" label="Order Prefix" />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="settingsData.orders.order_digits" label="Order Digits" type="number" />
                </VCol>
                <VCol cols="12">
                  <h6 class="text-h6 mt-4 mb-4">Plans & Collections</h6>
                </VCol>
                <VCol cols="12" md="6">
                  <AppSelect
                    v-model="welcomePlansArray"
                    label="Welcome Plans"
                    :items="plansList"
                    item-title="name"
                    item-value="id"
                    multiple
                    chips
                    closable-chips
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="settingsData.collections.order_follow_up_hours" label="Follow-up Hours" type="number" />
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- Financial Formulas -->
            <VWindowItem value="financial_formulas">
              <VRow>
                <VCol cols="12">
                  <h6 class="text-h5 mb-2">المعادلات المالية (Financial Formulas)</h6>
                  <p class="text-caption text-medium-emphasis mb-4 text-white">
                    المتغيرات المتاحة للاستخدام في المعادلات (يرجى كتابتها بالإنجليزية تماماً كما هي):
                    <br />
                    • <code>total_amount</code> : إجمالي قيمة الأوردر (المبلغ المطلوب تحصيله عند الاستلام).
                    <br />
                    • <code>shipping_fee</code> : مصاريف الشحن المفروضة على العميل (التاجر).
                    <br />
                    • <code>commission_amount</code> : عمولة المندوب .
                    <br />
                    • <code>company_amount</code> : صافي ربح الشركة من الشحن.
                    <br />
                    • <code>cod_amount</code> : مبلغ التحصيل المستحق للتاجر (قيمة المنتج قبل خصم رسوم التسوية).
                    <br />
                    • <code>settlement_fees</code> : رسوم تسوية الحساب الإضافية المخصومة من التاجر.
                  </p>
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField
                    v-model="settingsData.financial_formulas.formula_company_amount"
                    label="صافي ربح الشركة من الشحن (formula_company_amount)"
                    placeholder="shipping_fee - commission_amount"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField
                    v-model="settingsData.financial_formulas.formula_cod_amount"
                    label="مبلغ التحصيل المستحق للعميل - COD (formula_cod_amount)"
                    placeholder="total_amount - shipping_fee"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField
                    v-model="settingsData.financial_formulas.formula_shipper_collection_net_amount"
                    label="صافي تحصيل المندوب - المستحق للخزينة (formula_shipper_collection_net_amount)"
                    placeholder="total_amount - commission_amount"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField
                    v-model="settingsData.financial_formulas.formula_client_settlement_net_amount"
                    label="صافي تسوية العميل - صافي مستحقات التاجر (formula_client_settlement_net_amount)"
                    placeholder="cod_amount - settlement_fees"
                  />
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- WhatsApp -->
            <VWindowItem value="whatsapp">
              <VRow>
                <VCol cols="12">
                  <h6 class="text-h6 mb-2">سجل الأوردرات على واتساب</h6>
                  <p class="text-body-2 mb-4">
                    عند التفعيل، يتم إرسال رسالة لجروب واتساب مع كل تغيير على الأوردر (حالة، مبالغ، تحصيل، إلخ).
                    الخدمة لازم تكون شغّالة على السيرفر بشكل دائم.
                  </p>
                </VCol>

                <VCol cols="12">
                  <VAlert
                    :type="whatsappStatus?.ready ? 'success' : 'warning'"
                    variant="tonal"
                    class="mb-4"
                  >
                    {{
                      whatsappStatus?.ready
                        ? 'خدمة الواتساب متصلة وجاهزة للإرسال'
                        : (whatsappStatus?.message || 'جاري التحقق من حالة الخدمة...')
                    }}
                  </VAlert>
                  <div class="d-flex flex-wrap gap-2 mb-4">
                    <VBtn
                      size="small"
                      variant="tonal"
                      color="secondary"
                      @click="checkWhatsAppStatus"
                    >
                      تحديث حالة الاتصال
                    </VBtn>
                    <VBtn
                      v-if="whatsappStatus?.ready"
                      size="small"
                      variant="tonal"
                      color="primary"
                      :loading="whatsappGroupsLoading"
                      @click="fetchWhatsAppGroups"
                    >
                      تحديث الجروبات
                    </VBtn>
                  </div>
                </VCol>

                <VCol
                  v-if="!whatsappStatus?.ready"
                  cols="12"
                  md="6"
                >
                  <VCard variant="outlined">
                    <VCardText class="text-center">
                      <div class="text-subtitle-1 mb-3">
                        تسجيل الدخول بواتساب
                      </div>
                      <div
                        v-if="whatsappQrLoading"
                        class="py-8"
                      >
                        <VProgressCircular
                          indeterminate
                          color="primary"
                        />
                      </div>
                      <img
                        v-else-if="whatsappQr"
                        :src="whatsappQr"
                        alt="WhatsApp QR Code"
                        class="whatsapp-qr-image mb-3"
                      >
                      <p
                        v-else
                        class="text-body-2 text-medium-emphasis mb-0"
                      >
                        انتظر حتى يظهر QR Code (يتم التحديث تلقائياً كل 5 ثواني)
                      </p>
                      <p
                        v-if="whatsappQr"
                        class="text-caption text-medium-emphasis mt-3 mb-0"
                      >
                        افتح واتساب → الأجهزة المرتبطة → ربط جهاز → امسح الكود
                      </p>
                    </VCardText>
                  </VCard>
                </VCol>

                <VCol cols="12" md="6">
                  <VSwitch
                    v-model="settingsData.whatsapp.whatsapp_enabled"
                    label="تفعيل إرسال سجل الأوردرات على واتساب"
                    true-value="true"
                    false-value="false"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <VSwitch
                    v-model="settingsData.whatsapp.whatsapp_replace_order_notifications"
                    label="إيقاف إشعارات قاعدة البيانات لحالة الأوردر"
                    true-value="yes"
                    false-value="no"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppAutocomplete
                    v-model="whatsappGroupsArray"
                    label="اختر جروب واتساب"
                    placeholder="اختر الجروبات المراد الإرسال عليها"
                    :items="whatsappGroups"
                    item-title="name"
                    item-value="id"
                    multiple
                    chips
                    closable-chips
                    :loading="whatsappGroupsLoading"
                    :disabled="!whatsappStatus?.ready"
                    clearable
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField
                    v-model="settingsData.whatsapp.whatsapp_group_id"
                    label="معرّف الجروب (يدوي - مفصولة بفاصلة ,)"
                    placeholder="120363123456789012@g.us, 120363987654321012@g.us"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField
                    v-model="settingsData.whatsapp.whatsapp_service_url"
                    label="رابط خدمة الواتساب المجانية (whatsapp-service)"
                    placeholder="http://127.0.0.1:3001"
                    hint="مجاني بالكامل عبر whatsapp-web.js — مش UltraMsg. شغّل مجلد whatsapp-service بـ PM2."
                    persistent-hint
                  />
                </VCol>

                <VCol cols="12">
                  <VDivider class="my-4" />
                  <div class="d-flex align-center justify-space-between mb-4">
                    <div>
                      <h6 class="text-h6">ربط المجموعات بالعملاء</h6>
                      <p class="text-caption text-medium-emphasis mb-0">
                        اختر المجموعات وحدد لكل مجموعة العملاء المراد إرسال أوردراتهم إليها
                      </p>
                    </div>
                    <VBtn
                      size="small"
                      prepend-icon="tabler-plus"
                      @click="addGroupMapping"
                    >
                      إضافة ربط جديد
                    </VBtn>
                  </div>

                  <div v-if="groupMappings.length === 0" class="text-center py-6 border rounded dashed mb-4">
                    <p class="text-body-2 text-medium-emphasis mb-0">
                      لم يتم ربط أي مجموعة بعملاء بعد. سيتم الإرسال لكافة المجموعات الافتراضية المحددة أعلاه.
                    </p>
                  </div>

                  <VCard v-for="(mapping, index) in groupMappings" :key="index" variant="outlined" class="mb-4 pa-4">
                    <VRow align="center">
                      <VCol cols="12" md="5">
                        <VCombobox
                          v-model="mapping.group_id"
                          :items="whatsappGroups"
                          item-title="name"
                          item-value="id"
                          :return-object="false"
                          label="جروب الواتساب"
                          placeholder="اختر الجروب أو اكتب المعرّف يدوياً"
                          density="compact"
                          hide-details="auto"
                        />
                      </VCol>
                      <VCol cols="12" md="6">
                        <AppAutocomplete
                          v-model="mapping.client_ids"
                          :items="clients"
                          item-title="name"
                          item-value="id"
                          multiple
                          chips
                          closable-chips
                          label="العملاء المرتبطين"
                          placeholder="اختر العملاء الذين يتم إرسال أوردراتهم لهذا الجروب"
                          density="compact"
                          hide-details="auto"
                        />
                      </VCol>
                      <VCol cols="12" md="1" class="text-center">
                        <VBtn
                          icon
                          color="error"
                          variant="text"
                          size="small"
                          @click="removeGroupMapping(index)"
                        >
                          <VIcon icon="tabler-trash" />
                        </VBtn>
                      </VCol>
                    </VRow>
                  </VCard>
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- Themes -->
            <VWindowItem value="site_theme">
              <VRow>
                <VCol cols="12">
                   <h6 class="text-h6 mb-4">Primary Site Color</h6>
                   <p class="text-body-2 mb-4">Control the primary theme color of your platform.</p>
                </VCol>
                <VCol cols="12" md="6">
                   <AppTextField v-model="settingsData.site_theme.site_color_primary_light" label="Primary (Light Mode)" type="color" />
                </VCol>
                <VCol cols="12" md="6">
                   <AppTextField v-model="settingsData.site_theme.site_color_primary_dark" label="Primary (Dark Mode)" type="color" />
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- Social Media -->
            <VWindowItem value="social_media">
              <VRow>
                <VCol cols="12" md="6">
                  <AppTextField
                    v-model="settingsData.social_media.social_facebook"
                    label="Facebook URL"
                    prepend-inner-icon="tabler-brand-facebook"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField
                    v-model="settingsData.social_media.social_instagram"
                    label="Instagram URL"
                    prepend-inner-icon="tabler-brand-instagram"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField
                    v-model="settingsData.social_media.social_x"
                    label="X (Twitter) URL"
                    prepend-inner-icon="tabler-brand-x"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField
                    v-model="settingsData.social_media.social_whatsapp"
                    label="WhatsApp Number"
                    prepend-inner-icon="tabler-brand-whatsapp"
                  />
                </VCol>
              </VRow>
            </VWindowItem>
          </VWindow>

          <VDivider class="my-6" />

          <div class="d-flex justify-end gap-4">
            <VBtn color="primary" @click="updateSettings">Save Changes</VBtn>
            <VBtn color="secondary" variant="tonal" @click="fetchSettings">Reset</VBtn>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
  <div v-else class="text-center py-10">
     <VProgressCircular indeterminate color="primary" />
  </div>
</template>

<style scoped>
.whatsapp-qr-image {
  inline-size: 280px;
  max-inline-size: 100%;
  block-size: auto;
  border-radius: 8px;
}
</style>
