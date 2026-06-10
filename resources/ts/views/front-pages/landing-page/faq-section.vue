<script setup lang="ts">
const faqData = [
  {
    question: 'كيف يمكنني البدء مع Shipya؟',
    answer: 'يمكنك البدء بإنشاء حساب تجريبي مجاني لمدة 14 يوم، وبعدها يمكنك اختيار الخطة التي تناسب حجم أعمالك.',
  },
  {
    question: 'هل توجد عمولات خفية؟',
    answer: 'لا، نحن نعتمد على نظام اشتراك شهري واضح ولا نأخذ أي عمولات إضافية على الأوردرات إلا المذكورة في خطتك.',
  },
  {
    question: 'كيف يتم تسوية المبالغ المالية؟',
    answer: 'يتم تسوية المبالغ المحصلة من المناديب بشكل يومي، ويمكنك طلب تحويل مستحقاتك في أي وقت لتصلك خلال 24 ساعة.',
  },
  {
    question: 'هل تدعم المنصة ربط الـ API؟',
    answer: 'نعم، لدينا API متكامل وسهل الاستخدام للربط مع متجرك الإلكتروني (Shopify, WooCommerce, Salla) أو أي نظام خاص.',
  },
]
</script>

<template>
  <div id="faq-section" class="faq-wrapper py-16">
    <VContainer>
      <div class="headers d-flex justify-center flex-column align-center mb-12">
        <VChip variant="tonal" color="primary" class="mb-4">دعم العملاء</VChip>
        <h2 class="section-headline text-center mb-4">الأسئلة الشائعة</h2>
        <p class="section-subline text-center">كل ما تود معرفته عن خدمات المنصة وكيفية البدء.</p>
      </div>

      <VRow class="justify-center">
        <VCol cols="12" md="8">
          <VExpansionPanels elevation="0" variant="accordion" class="custom-panels">
            <VExpansionPanel
              v-for="faq in faqData"
              :key="faq.question"
              class="mb-4 glass-panel"
            >
              <VExpansionPanelTitle class="font-weight-bold text-h6 py-6">
                {{ faq.question }}
              </VExpansionPanelTitle>
              <VExpansionPanelText class="text-body-1 opacity-70 pb-6">
                {{ faq.answer }}
              </VExpansionPanelText>
            </VExpansionPanel>
          </VExpansionPanels>
        </VCol>
      </VRow>
    </VContainer>
  </div>
</template>

<style lang="scss" scoped>
@import "https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Cairo:wght@400;700;900&display=swap";

.faq-wrapper {
  background: white;
  font-family: Cairo, Outfit, sans-serif;
}

.dark .faq-wrapper { background: #0f172a; }

.section-headline {
  color: #1e293b;
  font-size: 2.25rem;
  font-weight: 900;
}
.dark .section-headline { color: white; }

.section-subline {
  color: #64748b;
  font-size: 1.1rem;
  max-inline-size: 600px;
}

.custom-panels {
  background: transparent !important;
}

.glass-panel {
  overflow: hidden;
  border: 1px solid rgba(0, 0, 0, 3%) !important;
  border-radius: 20px !important;
  background: rgba(248, 250, 252, 80%) !important;
  transition: all 0.3s ease;

  &::before { display: none; }

  &:hover {
    border-color: rgba(var(--v-theme-primary), 0.2) !important;
    background: white !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 3%) !important;
  }
}

.dark .glass-panel {
  border: 1px solid rgba(255, 255, 255, 5%) !important;
  background: rgba(30, 41, 59, 30%) !important;
  &:hover { background: rgba(30, 41, 59, 50%) !important; }
}

:deep(.v-expansion-panel-title__overlay) {
  display: none;
}
</style>

