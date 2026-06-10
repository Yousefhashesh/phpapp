<script setup lang="ts">
import PrintDocumentHeader from '@/components/prints/PrintDocumentHeader.vue'
import { formatPrintDate, formatPrintMoney, resolveLabelTrackingCode } from '@/utils/printDocuments'
import {
  formatStatusAr,
  formatTrackingDateTime,
  getTrackingActionLabel,
  getTrackingDeviceInfo,
  getTrackingLogMessage,
  getTrackingPerformer,
  type OrderTrackingLog,
} from '@/utils/orderTrackingReport'
import { computed } from 'vue'

const props = defineProps<{
  order: Record<string, any>
  settings: Record<string, any> | null
  logs: OrderTrackingLog[]
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

const flags = computed(() => [
  { label: 'تحصيل المندوب', value: props.order.is_shipper_collected },
  { label: 'مرتجع المندوب', value: props.order.is_shipper_returned },
  { label: 'تسوية العميل', value: props.order.is_client_settled },
  { label: 'مرتجع العميل', value: props.order.is_client_returned },
])
</script>

<template>
  <div class="tracking-report pa-6" dir="rtl">
    <PrintDocumentHeader
      :settings="settings"
      title="تقرير تتبع الطلب"
      subtitle="سجل كامل لحركة الطلب والتعديلات"
      :meta-lines="[
        { label: 'كود التتبع', value: trackingCode },
        { label: 'كود النظام', value: order.code || '—' },
        { label: 'الحالة الحالية', value: formatStatusAr(order.status) },
        { label: 'تاريخ الإنشاء', value: formatPrintDate(order.created_at) },
      ]"
    />

    <VDivider class="mb-6" />

    <div class="mb-6 pa-4 summary-strip">
      <div class="text-subtitle-1 font-weight-bold mb-4">
        ملخص الطلب
      </div>
      <VRow dense>
        <VCol cols="12" sm="6" md="3">
          <span class="meta-label">العميل (الراسل)</span>
          <div class="meta-value">{{ order.client?.name || '—' }}</div>
        </VCol>
        <VCol cols="12" sm="6" md="3">
          <span class="meta-label">المندوب</span>
          <div class="meta-value">{{ order.shipper?.name || 'غير محدد' }}</div>
        </VCol>
        <VCol cols="12" sm="6" md="3">
          <span class="meta-label">المستلم</span>
          <div class="meta-value">{{ order.receiver_name || '—' }}</div>
        </VCol>
        <VCol cols="12" sm="6" md="3">
          <span class="meta-label">الهاتف</span>
          <div class="meta-value">
            {{ order.phone }}{{ order.phone_2 ? ` / ${order.phone_2}` : '' }}
          </div>
        </VCol>
        <VCol cols="12">
          <span class="meta-label">العنوان</span>
          <div class="meta-value">{{ addressText }}</div>
        </VCol>
        <VCol cols="6" sm="3">
          <span class="meta-label">إجمالي الطلب</span>
          <div class="meta-value">{{ formatPrintMoney(order.total_amount) }}</div>
        </VCol>
        <VCol cols="6" sm="3">
          <span class="meta-label">COD</span>
          <div class="meta-value">{{ formatPrintMoney(order.cod_amount) }}</div>
        </VCol>
        <VCol cols="6" sm="3">
          <span class="meta-label">الشحن</span>
          <div class="meta-value">{{ formatPrintMoney(order.shipping_fee) }}</div>
        </VCol>
        <VCol cols="6" sm="3">
          <span class="meta-label">صافي الشركة</span>
          <div class="meta-value font-weight-bold">{{ formatPrintMoney(order.company_amount) }}</div>
        </VCol>
      </VRow>

      <div class="d-flex flex-wrap gap-2 mt-4">
        <span
          v-for="flag in flags"
          :key="flag.label"
          class="flag-chip"
          :class="flag.value ? 'flag-chip--yes' : 'flag-chip--no'"
        >
          {{ flag.label }}: {{ flag.value ? 'نعم' : 'لا' }}
        </span>
      </div>
    </div>

    <div class="mb-4 d-flex justify-space-between align-center">
      <h4 class="text-h6 font-weight-bold mb-0">
        سجل الأحداث ({{ logs.length }})
      </h4>
      <span class="text-caption text-secondary">مرتب زمنياً من الأقدم إلى الأحدث</span>
    </div>

    <table v-if="logs.length" class="w-100 border-collapse mb-6 print-table">
      <thead>
        <tr>
          <th style="width: 36px;">
            #
          </th>
          <th style="width: 140px;">
            التاريخ والوقت
          </th>
          <th style="width: 110px;">
            الإجراء
          </th>
          <th>التفاصيل</th>
          <th style="width: 100px;">
            بواسطة
          </th>
          <th style="width: 120px;">
            الجهاز / IP
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(log, index) in logs"
          :key="log.id"
        >
          <td class="text-center">
            {{ index + 1 }}
          </td>
          <td class="text-no-wrap">
            {{ formatTrackingDateTime(log.created_at) }}
          </td>
          <td>{{ getTrackingActionLabel(log) }}</td>
          <td class="text-wrap">
            {{ getTrackingLogMessage(log) }}
          </td>
          <td>{{ getTrackingPerformer(log) }}</td>
          <td class="text-xs">
            {{ getTrackingDeviceInfo(log) }}
          </td>
        </tr>
      </tbody>
    </table>

    <div
      v-else
      class="empty-logs pa-6 text-center text-secondary mb-6"
    >
      لا توجد أحداث مسجلة لهذا الطلب حتى الآن.
    </div>

    <div
      v-if="order.order_note || order.latest_status_note"
      class="mb-6 pa-3 notes-box rounded"
    >
      <div v-if="order.order_note" class="mb-2">
        <span class="font-weight-bold text-caption">ملاحظة الطلب:</span>
        <p class="mb-0 text-body-2">
          {{ order.order_note }}
        </p>
      </div>
      <div v-if="order.latest_status_note">
        <span class="font-weight-bold text-caption">آخر ملاحظة حالة:</span>
        <p class="mb-0 text-body-2">
          {{ order.latest_status_note }}
        </p>
      </div>
    </div>

    <div class="mt-10 d-flex justify-space-between align-end signatures">
      <div class="signature-line">
        توقيع المستلم
      </div>
      <div class="signature-line">
        ختم الشركة
      </div>
      <div class="signature-line">
        توقيع المسؤول
      </div>
    </div>

    <div class="mt-6 pt-3 border-t text-center text-caption text-secondary">
      طبع بواسطة النظام — {{ formatPrintDate(new Date().toISOString()) }}
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap');

.tracking-report {
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
  margin-bottom: 2px;
}

.meta-value {
  font-size: 0.9rem;
  font-weight: 600;
}

.flag-chip {
  font-size: 0.75rem;
  padding: 4px 10px;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
}

.flag-chip--yes {
  background: #ecfdf5;
  color: #047857;
  border-color: #a7f3d0;
}

.flag-chip--no {
  background: #f8fafc;
  color: #64748b;
}

.print-table {
  width: 100%;
  border-collapse: collapse;
}

.print-table th {
  background-color: #f1f5f9;
  border: 1px solid #e2e8f0;
  padding: 8px 10px;
  text-align: right;
  font-size: 0.82rem;
}

.print-table td {
  border: 1px solid #e2e8f0;
  padding: 7px 10px;
  font-size: 0.8rem;
  vertical-align: top;
}

.empty-logs {
  border: 1px dashed #cbd5e1;
  border-radius: 8px;
}

.notes-box {
  background: #fffbeb;
  border: 1px solid #fde68a;
}

.signatures .signature-line {
  min-inline-size: 140px;
  text-align: center;
  border-top: 1px solid #000;
  padding-top: 8px;
  font-size: 0.85rem;
}

@media print {
  .tracking-report {
    margin: 0;
    padding: 10mm !important;
    box-shadow: none !important;
  }

  @page {
    margin: 8mm;
    size: A4;
  }

  .print-table th,
  .flag-chip--yes {
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
  }
}
</style>
