<script setup lang="ts">
import PrintDocumentHeader from '@/components/prints/PrintDocumentHeader.vue'
import {
  formatPrintDate,
  formatPrintMoney,
  resolveLabelTrackingCode,
} from '@/utils/printDocuments'
import { computed } from 'vue'

const props = defineProps<{
  order: Record<string, any>
  settings: Record<string, any> | null
}>()

const trackingCode = computed(() => resolveLabelTrackingCode({
  code: props.order.code,
  external_code: props.order.external_code,
  label_code: props.order.label_code,
}))

const addressText = computed(() => {
  const parts = [
    props.order.governorate?.name,
    props.order.city?.name,
    props.order.address,
  ].filter(Boolean)

  return parts.join(' — ') || '—'
})
</script>

<template>
  <div class="invoice-print pa-6" dir="rtl">
    <PrintDocumentHeader
      :settings="settings"
      title="فاتورة شحنة"
      :meta-lines="[
        { label: 'كود التتبع', value: trackingCode },
        { label: 'كود النظام', value: order.code || '—' },
        { label: 'التاريخ', value: formatPrintDate(order.created_at) },
      ]"
    />

    <VDivider class="mb-6" />

    <div class="mb-6 pa-4 summary-strip d-flex flex-wrap gap-6 justify-space-between">
      <div>
        <span class="meta-label">العميل (الراسل)</span>
        <div class="meta-value">{{ order.client?.name || '—' }}</div>
      </div>
      <div>
        <span class="meta-label">المندوب</span>
        <div class="meta-value">{{ order.shipper?.name || '—' }}</div>
      </div>
      <div>
        <span class="meta-label">الحالة</span>
        <VChip size="small" color="primary" variant="elevated">{{ order.status }}</VChip>
      </div>
    </div>

    <table class="w-100 border-collapse mb-6 print-table">
      <thead>
        <tr>
          <th>كود التتبع</th>
          <th>كود النظام</th>
          <th>المستلم</th>
          <th>الهاتف</th>
          <th>العنوان</th>
          <th>المبلغ</th>
          <!-- <th>COD</th>
          <th>الشحن</th>
          <th>الصافي</th> -->
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="font-weight-bold">{{ trackingCode }}</td>
          <td>{{ order.code }}</td>
          <td>{{ order.receiver_name }}</td>
          <td>{{ order.phone }}{{ order.phone_2 ? ` / ${order.phone_2}` : '' }}</td>
          <td class="text-wrap">{{ addressText }}</td>
          <td>{{ formatPrintMoney(order.total_amount) }}</td>
          <!-- <td>{{ formatPrintMoney(order.cod_amount) }}</td>
          <td>{{ formatPrintMoney(order.shipping_fee) }}</td>
          <td>{{ formatPrintMoney(order.company_amount) }}</td> -->
        </tr>
      </tbody>
    </table>

    <div class="summary-box pa-4 rounded-lg border">
      <VRow>
        <VCol cols="12" sm="3">
          <div class="text-subtitle-2 text-secondary">إجمالي الطلب</div>
          <div class="text-h6 font-weight-bold text-success">{{ formatPrintMoney(order.total_amount) }}</div>
        </VCol>
        <!-- <VCol cols="12" sm="3">
          <div class="text-subtitle-2 text-secondary">مبلغ التحصيل (COD)</div>
          <div class="text-h6 font-weight-bold text-success">{{ formatPrintMoney(order.cod_amount) }}</div>
        </VCol> -->
        <!-- <VCol cols="12" sm="3">
          <div class="text-subtitle-2 text-secondary">مصاريف الشحن</div>
          <div class="text-h6 font-weight-bold">{{ formatPrintMoney(order.shipping_fee) }}</div>
        </VCol>
        <VCol cols="12" sm="3">
          <div class="text-subtitle-2 text-secondary">صافي الشركة</div>
          <div class="text-h5 font-weight-black text-primary">{{ formatPrintMoney(order.company_amount) }}</div>
        </VCol> -->
      </VRow>
    </div>

    <div
      v-if="order.order_note || order.latest_status_note"
      class="mt-4 pa-3 notes-box rounded"
    >
      <div v-if="order.order_note">
        <span class="font-weight-bold text-caption">ملاحظة الأوردر:</span>
        <p class="mb-1 text-body-2">{{ order.order_note }}</p>
      </div>
      <div v-if="order.latest_status_note">
        <span class="font-weight-bold text-caption">ملاحظة الحالة:</span>
        <p class="mb-0 text-body-2">{{ order.latest_status_note }}</p>
      </div>
    </div>

    <div class="mt-12 d-flex justify-space-between align-end signatures">
      <div class="signature-line">توقيع المستلم</div>
      <div class="signature-line">ختم الشركة</div>
      <div class="signature-line">توقيع المسؤول</div>
    </div>

    <div class="mt-6 pt-3 border-t text-center text-caption text-secondary">
      طبع بواسطة النظام — {{ formatPrintDate(new Date().toISOString()) }}
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap');

.invoice-print {
  background: white;
  min-height: 260mm;
  font-family: 'Cairo', sans-serif;
  color: #333;
}

.summary-strip {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.meta-label {
  display: block;
  font-size: 0.75rem;
  color: #64748b;
  margin-bottom: 4px;
}

.meta-value {
  font-weight: 700;
  font-size: 1rem;
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
  font-size: 0.85rem;
}

.print-table td {
  border: 1px solid #e2e8f0;
  padding: 8px 10px;
  font-size: 0.82rem;
}

.summary-box {
  background-color: #f8fafc;
}

.notes-box {
  background: #fffbeb;
  border: 1px solid #fde68a;
}

.signature-line {
  min-inline-size: 140px;
  border-top: 1px solid #000;
  padding-top: 8px;
  text-align: center;
  font-weight: 600;
}

@media print {
  .invoice-print {
    margin: 0;
    padding: 10mm !important;
    box-shadow: none !important;
    page-break-inside: avoid;
  }

  @page {
    margin: 8mm;
    size: A4;
  }

  .v-avatar,
  .v-chip,
  .v-divider {
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
  }
}
</style>
