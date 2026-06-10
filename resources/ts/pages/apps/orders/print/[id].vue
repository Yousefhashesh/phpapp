<script setup lang="ts">
import { useApi } from '@/composables/useApi'

const route = useRoute('apps-orders-print-id')
const id = route.params.id
const type = route.query.type as string // collection, return, settlement, client-return

const data = ref<any>(null)
const isLoading = ref(true)

const settings = ref<any>(null)

const fetchPrintData = async () => {
  let endpoint = ''
  if (type === 'collection') endpoint = `/shipper-collections/${id}`
  else if (type === 'return') endpoint = `/shipper-returns/${id}`
  else if (type === 'settlement') endpoint = `/client-settlements/${id}`
  else if (type === 'client-return') endpoint = `/client-returns/${id}`

  const [res, setRes] = await Promise.all([
    useApi<any>(endpoint).get().json(),
    useApi<any>('/settings').get().json(),
  ])

  data.value = res.data.value
  settings.value = setRes.data.value
  isLoading.value = false

  // Auto print after load
  setTimeout(() => {
    window.print()
  }, 1200)
}

onMounted(fetchPrintData)

const getTitle = () => {
  if (type === 'collection') return 'كشف تحصيل مندوب'
  if (type === 'return') return 'كشف مرتجعات مندوب'
  if (type === 'settlement') return 'كشف تحصيل عميل'
  if (type === 'client-return') return 'كشف مرتجعات عميل'
  return 'Invoice'
}

definePage({
  meta: {
    layout: 'blank',
  },
})
</script>

<template>
  <div v-if="!isLoading && data" class="invoice-print pa-6" dir="rtl">
    <!-- Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div class="d-flex align-center gap-3">
        <!-- Logo -->
        <VAvatar v-if="settings?.site_logos?.site_logo_512_light" rounded size="80" class="me-2">
          <VImg :src="settings.site_logos.site_logo_512_light" />
        </VAvatar>
        <VAvatar v-else rounded size="60" color="primary" variant="tonal">
          <VIcon icon="tabler-truck" size="40" />
        </VAvatar>
        
        <div>
          <h2 class="text-h4 font-weight-bold mb-0">
            {{ settings?.site_identity?.site_name || 'لوجو الشركة' }}
          </h2>
          <p class="text-caption mb-0 text-secondary">
            {{ settings?.site_identity?.site_address || 'نظام إدارة الشحن اللوجستي' }}
          </p>
        </div>
      </div>
      <div class="text-left">
        <h3 class="text-h3 font-weight-black mb-1 text-primary">{{ getTitle() }}</h3>
        <div class="text-subtitle-1">رقم الكشف: <span class="font-weight-bold">#{{ data.id }}</span></div>
        <div class="text-subtitle-2">التاريخ: {{ data.collection_date || data.return_date || data.settlement_date || '-' }}</div>
      </div>
    </div>

    <VDivider class="mb-6" />

    <!-- Summary Info -->
    <div class="mb-6 pa-4 bg-light rounded d-flex justify-space-between align-center">
      <div v-if="data.shipper" class="d-flex flex-column">
        <span class="text-subtitle-2 text-secondary">المندوب:</span>
        <span class="text-h6 font-weight-bold">{{ data.shipper.name }}</span>
      </div>
      <div v-if="data.client" class="d-flex flex-column">
        <span class="text-subtitle-2 text-secondary">العميل:</span>
        <span class="text-h6 font-weight-bold">{{ data.client.name }}</span>
      </div>
      <div class="text-left">
        <span class="text-subtitle-2 text-secondary">حالة الكشف:</span>
        <VChip size="small" color="primary" variant="elevated" class="ms-2">{{ data.status }}</VChip>
      </div>
    </div>

    <!-- Tables based on Type -->
    
    <!-- 1. Client Return (مرتجعات عميل) -->
    <div v-if="type === 'client-return'">
      <table class="w-100 border-collapse mb-6 print-table">
        <thead>
          <tr>
            <th>كود الطلب</th>
            <th>المستلم</th>
            <th>المحافظة</th>
            <th>النوع</th>
            <th>تاريخ التسليم</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in data.orders" :key="order.id">
            <td>{{ order.code }}</td>
            <td>{{ order.receiver_name }}</td>
            <td>{{ order.governorate?.name || order.city?.name }}</td>
            <td>{{ order.status.replace(/_/g, ' ') }}</td>
            <td>{{ order.created_at ? new Date(order.created_at).toLocaleDateString() : '-' }}</td>
          </tr>
        </tbody>
      </table>
      <div class="text-h6 font-weight-bold text-left border-t pt-4">
        إجمالي عدد المرتجعات: {{ data.number_of_orders || data.orders.length }}
      </div>
    </div>

    <!-- 2. Client Settlement (تحصيل عميل) -->
    <div v-if="type === 'settlement'">
      <table class="w-100 border-collapse mb-6 print-table">
        <thead>
          <tr>
            <th>كود الطلب</th>
            <th>المستلم</th>
            <th>رقم الهاتف</th>
            <th>الحالة</th>
            <th>مرتجع</th>
            <th>المبلغ</th>
            <th>المصاريف</th>
            <th>الصافي</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in data.orders" :key="order.id">
            <td>{{ order.code }}</td>
            <td>{{ order.receiver_name }}</td>
            <td>{{ order.phone }}</td>
            <td>{{ order.status.replace(/_/g, ' ') }}</td>
            <td>{{ order.status === 'UNDELIVERED' ? 'نعم' : 'لا' }}</td>
            <td>{{ Number(order.total_amount).toFixed(2) }}</td>
            <td>{{ Number(order.shipping_fee || 0).toFixed(2) }}</td>
            <td>{{ Number((order.pivot?.net_amount || order.total_amount - (order.shipping_fee || 0))).toFixed(2) }}</td>
          </tr>
        </tbody>
      </table>
      <div class="summary-box pa-4 rounded-lg bg-light border">
        <VRow>
          <VCol cols="3"><div class="text-subtitle-2">إجمالي عدد الطلبات:</div><div class="text-h6 font-weight-bold">{{ data.number_of_orders }}</div></VCol>
          <VCol cols="3"><div class="text-subtitle-2">إجمالي التحصيل:</div><div class="text-h6 font-weight-bold">{{ Number(data.total_amount).toFixed(2) }} ج.م</div></VCol>
          <VCol cols="3"><div class="text-subtitle-2">إجمالي مصاريف الشركة:</div><div class="text-h6 font-weight-bold text-error">{{ Number(data.fees).toFixed(2) }} ج.م</div></VCol>
          <VCol cols="3"><div class="text-subtitle-2">الصافي المستحق للعميل:</div><div class="text-h4 font-weight-black text-success">{{ Number(data.net_amount).toFixed(2) }} ج.م</div></VCol>
        </VRow>
      </div>
    </div>

    <!-- 3. Shipper Collection (تحصيل مندوب) -->
    <div v-if="type === 'collection'">
      <table class="w-100 border-collapse mb-6 print-table">
        <thead>
          <tr>
            <th>كود الطلب</th>
            <th>المستلم</th>
            <th>رقم الهاتف</th>
            <th>مرتجع</th>
            <th>المبلغ</th>
            <th>عمولة المندوب</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in data.orders" :key="order.id">
            <td>{{ order.code }}</td>
            <td>{{ order.receiver_name }}</td>
            <td>{{ order.phone }}</td>
            <td>{{ order.status === 'UNDELIVERED' ? 'نعم' : 'لا' }}</td>
            <td>{{ Number(order.total_amount || 0).toFixed(2) }}</td>
            <td>{{ Number(order.total_amount - (order.company_amount || 0)).toFixed(2) }}</td>
          </tr>
        </tbody>
      </table>
      <div class="summary-box pa-4 rounded-lg bg-light border">
        <VRow>
          <VCol cols="3"><div class="text-subtitle-2">إجمالي عدد الطلبات:</div><div class="text-h6 font-weight-bold">{{ data.number_of_orders }}</div></VCol>
          <VCol cols="3"><div class="text-subtitle-2">إجمالي التحصيل:</div><div class="text-h6 font-weight-bold">{{ Number(data.total_amount).toFixed(2) }} ج.م</div></VCol>
          <VCol cols="3"><div class="text-subtitle-2">إجمالي عمولة المندوب:</div><div class="text-h6 font-weight-bold">{{ Number(data.total_amount - data.net_amount).toFixed(2) }} ج.م</div></VCol>
          <VCol cols="3"><div class="text-subtitle-2">الصافي المستلم من المندوب:</div><div class="text-h4 font-weight-black text-primary">{{ Number(data.net_amount).toFixed(2) }} ج.م</div></VCol>
        </VRow>
      </div>
    </div>

    <!-- 4. Shipper Returns (مرتجعات مندوب) -->
    <div v-if="type === 'return'">
      <table class="w-100 border-collapse mb-6 print-table">
        <thead>
          <tr>
            <th>كود الطلب</th>
            <th>العميل</th>
            <th>المكان / المحافظة</th>
            <th>المستلم</th>
            <th>حالة الطلب</th>
            <th>ملاحظات المرتجع</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in data.orders" :key="order.id">
            <td>{{ order.code }}</td>
            <td>{{ order.client?.name }}</td>
            <td>{{ order.governorate?.name || order.city?.name }}</td>
            <td>{{ order.receiver_name }}</td>
            <td>{{ order.status.replace(/_/g, ' ') }}</td>
            <td class="text-wrap" style="max-width: 200px;">{{ order.latest_status_note || '-' }}</td>
          </tr>
        </tbody>
      </table>
      <div class="text-h6 font-weight-bold text-left border-t pt-4">
        إجمالي عدد المرتجعات: {{ data.number_of_orders }}
      </div>
    </div>

    <!-- Footer Seal/Signature -->
    <div class="mt-12 d-flex justify-space-between align-end">
      <div class="text-center" style="min-inline-size: 150px; border-top: 1px solid #000; padding-top: 8px;">إمضاء المستلم</div>
      <div class="text-center" style="min-inline-size: 150px; border-top: 1px solid #000; padding-top: 8px;">ختم الشركة</div>
      <div class="text-center" style="min-inline-size: 150px; border-top: 1px solid #000; padding-top: 8px;">إمضاء المسؤول</div>
    </div>

    <!-- Footer Info -->
    <div class="mt-8 pt-4 border-t text-center text-caption text-secondary">
      طبع بواسطة: {{ data.generator?.name || 'النظام' }} | الوقت: {{ new Date().toLocaleString('ar-EG') }}
    </div>
  </div>
  <div v-else class="pa-20 text-center">
    <VProgressCircular indeterminate color="primary" />
  </div>
</template>

<style scoped>
.invoice-print {
  background: white;
  min-height: 290mm;
  font-family: 'Cairo', sans-serif;
  color: #333;
}

.print-table {
  width: 100%;
  border-collapse: collapse;
}

.print-table th {
  background-color: #f1f5f9;
  border: 1px solid #e2e8f0;
  padding: 10px;
  text-align: right;
  font-size: 0.9rem;
}

.print-table td {
  border: 1px solid #e2e8f0;
  padding: 8px 10px;
  font-size: 0.85rem;
}

.summary-box {
  background-color: #f8fafc;
}

@media print {
  .pa-6 { padding: 0 !important; }
  .invoice-print { margin: 0; padding: 10mm !important; box-shadow: none !important; }
  @page { margin: 8mm; size: A4; }
  .v-avatar, .v-btn, .v-divider {
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
  }
}

/* RTL Helpers */
dir[rtl] .text-left { text-align: left !important; }
dir[rtl] .text-right { text-align: right !important; }
</style>
