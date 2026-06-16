<script setup lang="ts">
import {
  barcodeImageUrl,
  formatDistrictLine,
  formatLabelDate,
  formatLabelStatusAr,
  formatLabelTimestamp,
  formatPrintMoney,
  hasDistinctInternalCode,
  qrCodeImageUrl,
  resolveFullAddressLine,
  resolveLabelTrackingCode,
  resolveOrderContent,
  type ShippingLabelPayload,
} from '@/utils/printDocuments'
import { computed } from 'vue'

const props = defineProps<{
  data: ShippingLabelPayload
  settings: Record<string, any> | null
}>()

const trackingCode = computed(() => resolveLabelTrackingCode(props.data))
const showInternalCode = computed(() => hasDistinctInternalCode(props.data))
const districtLine = computed(() => formatDistrictLine(props.data))
const fullAddress = computed(() => resolveFullAddressLine(props.data))
const contentLine = computed(() => resolveOrderContent(props.data))

const companyName = computed(() => props.settings?.site_identity?.site_name || 'شركة الشحن')
const companyPhone = computed(() => props.settings?.site_identity?.site_phone || '')
const companyAddress = computed(() => props.settings?.site_identity?.site_address || '')
const logoUrl = computed(() => props.settings?.site_logos?.site_logo_512_light || '')

const barcodeSrc = computed(() => barcodeImageUrl(trackingCode.value, 52))
const qrSrc = computed(() => qrCodeImageUrl(trackingCode.value, 88))

const notesText = computed(() => {
  const parts = [
    props.data.order_note?.trim(),
    props.data.latest_status_note?.trim(),
  ].filter(Boolean)

  return parts.length ? parts.join(' — ') : '—'
})
</script>

<template>
  <article class="label-sheet" dir="rtl">
  <header class="label-header">
    <div class="label-header__brand">
      <img
        v-if="logoUrl"
        :src="logoUrl"
        alt="logo"
        class="label-logo"
      >
      <div class="label-header__company">
        <div class="label-header__company-name">
          {{ companyName }}
        </div>
        <div v-if="companyPhone" class="label-meta">
          هاتف: {{ companyPhone }}
        </div>
        <div v-if="companyAddress" class="label-meta">
          {{ companyAddress }}
        </div>
      </div>
    </div>

    <div class="label-header__title-block">
      <h1 class="label-title">
        بوليصة شحن
      </h1>
      <div class="label-codes">
        <div class="label-code-row">
          <span class="label-code-key">كود التتبع</span>
          <span class="label-code-val">{{ trackingCode }}</span>
        </div>
        <div
          v-if="showInternalCode"
          class="label-code-row"
        >
          <span class="label-code-key">كود النظام</span>
          <span class="label-code-val">{{ data.code }}</span>
        </div>
        <div
          v-if="data.external_code && data.external_code !== trackingCode"
          class="label-code-row"
        >
          <span class="label-code-key">كود خارجي</span>
          <span class="label-code-val">{{ data.external_code }}</span>
        </div>
      </div>
    </div>

    <div class="label-header__scan">
      <img
        :src="barcodeSrc"
        alt="barcode"
        class="label-barcode"
      >
      <img
        :src="qrSrc"
        alt="qr"
        class="label-qr"
      >
    </div>
  </header>

  <section class="label-grid label-grid--3">
    <div class="label-cell">
      <span class="label-cell__key">الراسل</span>
      <span class="label-cell__val">{{ data.client_name || '—' }}</span>
    </div>
    <div class="label-cell">
      <span class="label-cell__key">هاتف الراسل</span>
      <span class="label-cell__val">{{ data.client_phone || '—' }}</span>
    </div>
    <div class="label-cell">
      <span class="label-cell__key">محتوى الشحنة</span>
      <span class="label-cell__val">{{ data.shipping_content || '—' }}</span>
    </div>
  </section>

  <section class="label-highlight">
    <div class="label-highlight__block">
      <span class="label-highlight__key">المستلم</span>
      <span class="label-highlight__val">{{ data.receiver_name || '—' }}</span>
    </div>
    <div class="label-highlight__block label-highlight__block--phones">
      <span class="label-highlight__key">هاتف المستلم</span>
      <span class="label-highlight__val">{{ data.receiver_phones_text || data.phone || '—' }}</span>
    </div>
  </section>

  <section class="label-grid label-grid--2">
    <div class="label-cell">
      <span class="label-cell__key">المحافظة / المنطقة</span>
      <span class="label-cell__val label-cell__val--emphasis">{{ districtLine }}</span>
    </div>
    <div class="label-cell">
      <span class="label-cell__key">العنوان التفصيلي</span>
      <span class="label-cell__val">{{ data.street_address || fullAddress }}</span>
    </div>
  </section>

  

  <section class="label-amounts label-amounts--4">
   
    <div class="label-amount-box">
      <span class="label-amount-box__key">الإجمالي المطلوب</span>
      <span class="label-amount-box__val">{{ formatPrintMoney(data.total_amount) }}</span>
    </div>
   
  </section>

  

  <section class="label-policy">
    <div class="label-policy__item">
      <span
        class="label-check"
        :class="{ 'label-check--on': data.allow_open }"
      >{{ data.allow_open ? '✓' : '' }}</span>
      <span>مسموح بالفتح والمعاينة</span>
    </div>
    <div class="label-policy__item">
      <span
        class="label-check"
        :class="{ 'label-check--on': !data.allow_open }"
      >{{ !data.allow_open ? '✓' : '' }}</span>
      <span>ممنوع الفتح قبل الاستلام</span>
    </div>
    <div class="label-policy__content">
      <span class="label-cell__key">وصف المحتوى:</span>
      <span class="label-cell__val">{{ contentLine }}</span>
    </div>
  </section>

  <section
    v-if="notesText !== '—'"
    class="label-notes"
  >
    <span class="label-notes__key">ملاحظات : </span>
    <span class="label-notes__val">{{ notesText }}</span>
  </section>
  </article>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap');

.label-sheet {
  --label-border: 2px solid #000;
  --label-muted: #444;

  box-sizing: border-box;
  width: 100%;
  max-width: 190mm;
  margin: 0 auto;
  padding: 6mm;
  font-family: 'Cairo', sans-serif;
  color: #000;
  background: #fff;
  border: var(--label-border);
}

.label-header {
  display: grid;
  grid-template-columns: 1fr 1.1fr auto;
  gap: 8px;
  padding-bottom: 8px;
  margin-bottom: 8px;
  border-bottom: var(--label-border);
}

.label-header__brand {
  display: flex;
  gap: 8px;
  align-items: flex-start;
}

.label-logo {
  width: 42px;
  height: 42px;
  object-fit: contain;
  border: 1px solid #000;
  padding: 2px;
}

.label-header__company-name {
  font-size: 1rem;
  font-weight: 900;
  line-height: 1.2;
}

.label-meta {
  font-size: 0.68rem;
  color: var(--label-muted);
  line-height: 1.35;
}

.label-header__title-block {
  text-align: center;
  align-self: center;
}

.label-title {
  margin: 0 0 6px;
  font-size: 1.35rem;
  font-weight: 900;
  letter-spacing: 0.02em;
  color: #7367f0;
}

.label-codes {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.label-code-row {
  display: flex;
  justify-content: center;
  gap: 6px;
  font-size: 0.72rem;
}

.label-code-key {
  color: var(--label-muted);
  font-weight: 600;
}

.label-code-val {
  font-weight: 900;
  letter-spacing: 0.04em;
}

.label-header__scan {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.label-barcode {
  height: 44px;
  width: auto;
  max-width: 130px;
}

.label-qr {
  width: 52px;
  height: 52px;
}

.label-grid {
  display: grid;
  gap: 0;
  margin-bottom: 8px;
  border: var(--label-border);
}

.label-grid--2 {
  grid-template-columns: 1fr 1.4fr;
}

.label-grid--3 {
  grid-template-columns: repeat(3, 1fr);
}

.label-grid--4 {
  grid-template-columns: repeat(4, 1fr);
}

.label-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 6px 8px;
  border-left: 1px solid #000;
  min-height: 42px;
}

.label-cell:first-child {
  border-left: none;
}

.label-cell__key {
  font-size: 0.62rem;
  font-weight: 700;
  color: var(--label-muted);
  text-transform: uppercase;
}

.label-cell__val {
  font-size: 0.82rem;
  font-weight: 600;
  line-height: 1.35;
  word-break: break-word;
}

.label-cell__val--emphasis {
  font-size: 0.95rem;
  font-weight: 900;
}

.label-highlight {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 0;
  margin-bottom: 8px;
  border: 3px solid #000;
}

.label-highlight__block {
  padding: 10px 12px;
  border-left: 2px solid #000;
}

.label-highlight__block:first-child {
  border-left: none;
}

.label-highlight__key {
  display: block;
  font-size: 0.68rem;
  font-weight: 700;
  color: var(--label-muted);
  margin-bottom: 4px;
}

.label-highlight__val {
  display: block;
  font-size: 1.15rem;
  font-weight: 900;
  line-height: 1.25;
}

.label-highlight__block--phones .label-highlight__val {
  font-size: 1.05rem;
  letter-spacing: 0.02em;
}

.label-address-banner {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 8px 10px;
  margin-bottom: 8px;
  border: 3px double #000;
}

.label-address-banner__key {
  font-size: 0.65rem;
  font-weight: 700;
  color: var(--label-muted);
}

.label-address-banner__val {
  font-size: 0.95rem;
  font-weight: 800;
  line-height: 1.4;
}

.label-amounts {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0;
  margin-bottom: 8px;
  border: var(--label-border);
}

.label-amounts--4 {
  grid-template-columns: repeat(4, 1fr);
}

.label-amount-box {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 2px;
  padding: 8px;
  border-left: 1px solid #000;
  text-align: center;
}

.label-amount-box:first-child {
  border-left: none;
}

.label-amount-box__key {
  font-size: 0.62rem;
  font-weight: 700;
  color: var(--label-muted);
}

.label-amount-box__val {
  font-size: 0.95rem;
  font-weight: 900;
}

.label-policy {
  display: grid;
  grid-template-columns: auto auto 1fr;
  gap: 12px;
  align-items: center;
  padding: 8px 10px;
  margin-bottom: 8px;
  border: var(--label-border);
}

.label-policy__item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
}

.label-check {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  border: 2px solid #000;
  font-size: 0.7rem;
  font-weight: 900;
  line-height: 1;
}

.label-check--on {
  background: #000;
  color: #fff;
}

.label-policy__content {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  font-size: 0.78rem;
  border-right: 1px solid #000;
  padding-right: 12px;
}

.label-notes {
  display: flex;
  flex-direction: row;
  gap: 4px;
  padding: 8px 10px;
  margin-bottom: 8px;
  border: 1px dashed #000;
}

.label-notes__key {
  font-size: 0.65rem;
  font-weight: 700;
  color: var(--label-muted);
}

.label-notes__val {
  font-size: 0.8rem;
  font-weight: 600;
  line-height: 1.4;
}

.label-footer {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 8px;
  padding-top: 6px;
  border-top: 1px solid #000;
  font-size: 0.62rem;
  color: var(--label-muted);
  font-weight: 600;
}

@media print {
  @page {
    size: A5 landscape;
    margin: 3mm;
  }

  .label-sheet {
    max-width: none;
    width: 100%;
    min-height: 96vh;
    padding: 4mm;
    page-break-inside: avoid;
  }

  .label-check--on {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>
