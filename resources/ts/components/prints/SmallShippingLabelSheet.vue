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
  <div class="label-container">
    
    <div class="label-sheet" dir="rtl">
      <header class="label-header">
        <div class="label-header__title-block">
          <div class="label-header__scan">
            <img
              :src="barcodeSrc"
              alt="barcode"
              class="label-barcode"
            >
            <span class="label-code-val">{{ data.code }}</span>
          </div>
          <div class="label-codes">
            <div class="label-code-row">
              <span class="label-code-val">{{ trackingCode }}</span>
            </div>
            <div class="label-code-row label-code-row--wrap">
              <span class="label-code-val">{{ data.receiver_name }} - {{ fullAddress }}</span>
            </div>
            <div class="label-code-row">
              <span class="label-code-val">{{ data.phone }}</span>
            </div>
             <div class="label-code-row">
              <span class="label-code-key">السعر : </span>
              <span class="label-code-val">{{ formatPrintMoney(data.total_amount) }}</span>
            </div>
          </div>
        </div>
      </header>
    </div>

  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap');

html, body {
  margin: 0;
  padding: 0;
  background-color: #fff;
}

/* تنظيم ملصقات الشحن في شبكة من عمودين متساويين */
.labels-container {
  display: grid;
  grid-template-columns: repeat(2, 1fr); 
  gap: 12px; /* مسافة أمان صغيرة بين الملصقات لسهولة القص وعدم الهدر */
  padding: 10px;
  box-sizing: border-box;
  width: 100%;
}

.label-sheet {
  --label-border: 2px solid #000;
  box-sizing: border-box;
  
  width: 100%; 
  min-height: 35mm; /* تقليل الارتفاع لتناسب 16 ليبل في الصفحة */
  
  padding: 3mm; 
  font-family: 'Cairo', sans-serif;
  color: #000;
  background: #fff;
  border: var(--label-border);
  
  /* منع تقسيم الملصق الواحد بين صفحتين أثناء الطباعة */
  page-break-inside: avoid; 
  break-inside: avoid;
}

.label-header {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.label-header__title-block {
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.label-codes {
  display: flex;
  flex-direction: column;
  gap: 6px;
  width: 100%;
}

.label-code-row {
  display: flex;
  justify-content: center;
  font-size: 0.95rem; /* حجم خط واضح ومثالي للقراءة */
  text-align: center;
}

.label-code-row--wrap {
  word-break: break-word;
}

.label-code-val {
  font-weight: 900;
  line-height: 1.4;
}

.label-header__scan {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  width: 100%;
}

.label-barcode {
  height: 55px; /* حجم باركود ممتاز لسهولة القراءة بجهاز السكنر */
  max-width: 100%;
  object-fit: contain;
}

/* إعدادات المتصفح الافتراضية عند طلب أمر الطباعة */
@media print {
  @page {
    size: A4 portrait;
    margin: 8mm; /* هوامش خارجية بسيطة للورقة */
  }
  
  .labels-container {
    padding: 0;
  }

  .label-sheet {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>