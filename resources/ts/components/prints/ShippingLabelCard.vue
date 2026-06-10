<script setup lang="ts">
import {
  barcodeImageUrl,
  formatLabelTimestamp,
  hasDistinctInternalCode,
  resolveFragileNote,
  resolveFullAddressLine,
  resolveLabelTotalAmount,
  resolveLabelTrackingCode,
  resolveOrderContent,
  resolvePiecesCount,
  resolveSenderDisplay,
  type ShippingLabelPayload,
} from '@/utils/printDocuments'
import { computed } from 'vue'

const props = defineProps<{
  data: ShippingLabelPayload
  settings: Record<string, any> | null
}>()

const trackingCode = computed(() => resolveLabelTrackingCode(props.data))
const showInternalCode = computed(() => hasDistinctInternalCode(props.data))
const barcodeSrc = computed(() => barcodeImageUrl(trackingCode.value, 80))
const fullAddress = computed(() => resolveFullAddressLine(props.data))
const shipmentText = computed(() => resolveOrderContent(props.data))
const totalAmount = computed(() => resolveLabelTotalAmount(props.data))
const fragileNote = computed(() => resolveFragileNote(props.data))
const printedAt = computed(() => formatLabelTimestamp(props.data.created_at))
const companyName = computed(() => props.settings?.site_identity?.site_name || 'شركة الشحن')
const senderDisplay = computed(() => resolveSenderDisplay(props.data))
const senderPhone = computed(() => props.data.client_phone || '—')
const piecesCount = computed(() => resolvePiecesCount(props.data))
const systemCodeDisplay = computed(() => props.data.code || '000000')
</script>

<template>
  <div class="way-label-sheet" dir="rtl">
    <table class="way-label-table">
      <!-- Header -->
      <tbody>
        <tr>
          <td colspan="2" class="header-note">
            ملحوظة / {{ fragileNote }}
          </td>
          <td class="header-logo">
            <div class="logo-frame">
              <img
                v-if="settings?.site_logos?.site_logo_512_light"
                :src="settings.site_logos.site_logo_512_light"
                alt="logo"
                class="company-logo"
              >
              <span v-else class="logo-text">{{ companyName }}</span>
            </div>
          </td>
        </tr>

        <!-- Sender -->
        <tr>
          <td class="lbl">
            اسم الراسل
          </td>
          <td colspan="2" class="val">
            {{ senderDisplay }}
          </td>
        </tr>
        <tr>
          <td class="lbl">
            تليفون
          </td>
          <td colspan="2" class="val ltr">
            {{ senderPhone }}
          </td>
        </tr>

        <!-- Receiver -->
        <tr>
          <td class="lbl">
            المرسل إليه
          </td>
          <td colspan="2" class="val receiver">
            {{ data.receiver_name || '—' }}
          </td>
        </tr>

        <!-- Address + barcode column -->
        <tr>
          <td class="lbl">
            العنوان
          </td>
          <td class="val address">
            {{ fullAddress }}
          </td>
          <td class="barcode-col" rowspan="3">
            <div class="barcode-stack">
              <div class="barcode-wrap">
                <img
                  :src="barcodeSrc"
                  alt="barcode"
                  class="barcode-img"
                >
              </div>
              <div class="track-number ltr">
                {{ trackingCode }}
              </div>
              <div class="barcode-brand">
                {{ companyName }}
              </div>
              <div class="barcode-brand-sub ltr">
                {{ systemCodeDisplay }}
              </div>
            </div>
          </td>
        </tr>

        <!-- Summary -->
        <tr>
          <td colspan="2" class="summary-line">
            <span>عدد القطع || {{ piecesCount }}</span>
            <span class="total-part">الإجمالي : {{ totalAmount }}</span>
          </td>
        </tr>
        <tr>
          <td colspan="2" class="timestamp-line ltr">
            {{ printedAt }}
          </td>
        </tr>

        <!-- Shipment -->
        <tr>
          <td class="lbl shipment-lbl">
            الشحنة :
          </td>
          <td colspan="2" class="val shipment-val">
            {{ shipmentText }}
          </td>
        </tr>

        <tr v-if="showInternalCode">
          <td colspan="3" class="system-code ltr">
            كود النظام: {{ data.code }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap');

.way-label-sheet {
  font-family: 'Cairo', Arial, sans-serif;
  color: #000;
  background: #fff;
  width: 90mm;
  max-width: 90mm;
  margin: 0 auto;
  padding: 0;
}

.way-label-table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
  border: 1px solid #000;
}

.way-label-table td {
  border: 1px solid #000;
  padding: 3px 5px;
  vertical-align: top;
  font-size: 0.7rem;
  line-height: 1.35;
}

.header-note {
  font-weight: 700;
  font-size: 0.68rem;
  width: 62%;
}

.header-logo {
  width: 38%;
  text-align: center;
  vertical-align: middle;
  padding: 4px;
}

.logo-frame {
  border: 1px solid #000;
  padding: 4px 6px;
  min-height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.company-logo {
  max-height: 24px;
  max-width: 100%;
  object-fit: contain;
}

.logo-text {
  font-size: 0.62rem;
  font-weight: 800;
  line-height: 1.2;
}

.lbl {
  width: 22%;
  font-weight: 700;
  white-space: nowrap;
  background: #fff;
}

.val {
  font-weight: 600;
  word-break: break-word;
}

.receiver {
  font-weight: 800;
  font-size: 0.78rem;
}

.address {
  font-size: 0.72rem;
  line-height: 1.45;
  min-height: 52px;
}

.barcode-col {
  width: 30%;
  padding: 4px 3px !important;
  vertical-align: middle !important;
  text-align: center;
}

.barcode-stack {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3px;
  min-height: 88px;
}

.barcode-wrap {
  height: 58px;
  width: 52px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.barcode-img {
  height: 120px;
  width: auto;
  transform: rotate(90deg);
}

.track-number {
  font-size: 1.15rem;
  font-weight: 900;
  letter-spacing: 0.04em;
  line-height: 1;
}

.barcode-brand {
  font-size: 0.58rem;
  font-weight: 800;
  line-height: 1.2;
  text-align: center;
}

.barcode-brand-sub {
  font-size: 0.58rem;
  font-weight: 700;
}

.summary-line {
  font-weight: 700;
  font-size: 0.74rem;
  padding: 5px 6px !important;
}

.summary-line .total-part {
  float: left;
  font-size: 0.82rem;
  font-weight: 900;
}

.timestamp-line {
  font-size: 0.65rem;
  font-weight: 600;
  padding: 3px 6px !important;
  text-align: left;
}

.shipment-lbl {
  vertical-align: top;
}

.shipment-val {
  font-size: 0.7rem;
  min-height: 22px;
}

.system-code {
  font-size: 0.6rem;
  color: #333;
  text-align: center;
  padding: 2px !important;
}

.ltr {
  direction: ltr;
  text-align: left;
}

@media print {
  .way-label-sheet {
    width: 90mm;
    max-width: 90mm;
    margin: 0;
    page-break-inside: avoid;
  }

  @page {
    size: 90mm 130mm;
    margin: 2mm;
  }
}
</style>
