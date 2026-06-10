<script setup lang="ts">
const featuresData = [
  { title: 'نظام تتبع متطور', desc: 'تتبع كل شحنة بدقة متناهية من لحظة الخروج وحتى الاستلام النهائي مع سجل كامل للتحركات.', icon: 'tabler-map-2', color: '#6366f1' },
  { title: 'تسويات مالية ذكية', desc: 'إدارة كاملة للمبالغ المحصلة وتسويات العملاء والمندوبين بشكل آلي وبدون أخطاء محاسبية.', icon: 'tabler-calculator', color: '#0ea5e9' },
  { title: 'ماسح الباركود السريع', desc: 'نظام متكامل للمسح الضوئي لمعالجة المرتجعات والتسليمات بالجملة في ثوانٍ معدودة.', icon: 'tabler-scan', color: '#f43f5e' },
  { title: 'تقارير تحليلية', desc: 'عرض شامل لأداء شركتك من خلال لوحة تحكم ذكية توضح نسب التسليم والمبالغ تحت التحصيل.', icon: 'tabler-chart-pie', color: '#f59e0b' },
  { title: 'إدارة الفروع والمناديب', desc: 'تقسيم العمل على فروع ومناطق مختلفة مع تعيين تلقائي للمناديب بناءً على الموقع الجغرافي.', icon: 'tabler-users', color: '#8b5cf6' },
  { title: 'دعم فني متكامل', desc: 'فريق متخصص يقدم الدعم الفني لك ولعملائك لضمان استمرارية العمل بأفضل جودة.', icon: 'tabler-headset', color: '#10b981' },
]
</script>

<template>
  <div id="features" class="features-wrapper py-16">
    <VContainer>
      <div class="headers d-flex justify-center flex-column align-center mb-16">
        <VChip
          variant="tonal"
          color="primary"
          class="mb-6 font-weight-bold px-6"
        >
          مميزات المنصة
        </VChip>
        <h2 class="section-headline mb-4 text-center">
          كل ما تحتاجه لإدارة منظومة الشحن الخاصة بك
        </h2>
        <p class="section-subline mb-0 text-center">
          نحن لا نقدم مجرد أدوات، بل نقدم حلولاً لوجستية متكاملة لنمو أعمالك وتوسيع نطاق مبيعاتك.
        </p>
      </div>

      <VRow>
        <VCol
          v-for="(data, index) in featuresData"
          :key="index"
          cols="12"
          md="4"
          sm="6"
        >
          <div class="glass-feature-card pa-8 transition-all h-100">
            <div class="icon-wrapper mb-6" :style="{ backgroundColor: data.color + '15' }">
              <VIcon
                :icon="data.icon"
                size="36"
                :style="{ color: data.color }"
              />
            </div>
            <h5 class="feature-title mb-3">
              {{ data.title }}
            </h5>
            <p class="feature-desc mb-0">
              {{ data.desc }}
            </p>
          </div>
        </VCol>
      </VRow>

      <div class="text-center mt-16">
        <VBtn
          size="x-large"
          color="primary"
          elevation="0"
          class="premium-cta-btn rounded-pill px-10"
          :to="{ name: 'pages-authentication-login-v1' }"
        >
          ابدأ رحلتك المجانية الآن
          <VIcon icon="tabler-arrow-narrow-left" end class="ms-2" />
        </VBtn>
      </div>
    </VContainer>
  </div>
</template>

<style lang="scss" scoped>
@import "https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Cairo:wght@400;700;900&display=swap";

.features-wrapper {
  background: radial-gradient(circle at bottom right, rgba(var(--v-theme-primary), 0.03), transparent 40%);
  font-family: Cairo, Outfit, sans-serif;
}

.section-headline {
  color: #1e1e1e;
  font-size: 2.5rem;
  font-weight: 900;
  line-height: 1.2;
}

.dark .section-headline { color: white; }

.section-subline {
  color: #666;
  font-size: 1.1rem;
  max-inline-size: 700px;
}

.glass-feature-card {
  display: flex;
  flex-direction: column;
  border: 1px solid rgba(255, 255, 255, 30%);
  border-radius: 24px;
  backdrop-filter: blur(20px) saturate(180%);
  background: rgba(255, 255, 255, 40%);
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 5%);

  &:hover {
    border-color: rgba(var(--v-theme-primary), 0.2);
    background: rgba(255, 255, 255, 60%);
    box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 10%);
    transform: translateY(-8px);
  }
}

.dark .glass-feature-card {
  border: 1px solid rgba(255, 255, 255, 5%);
  background: rgba(30, 41, 59, 30%);
}

.icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 16px;
  block-size: 64px;
  inline-size: 64px;
  transition: all 0.3s ease;
}

.feature-title {
  color: #333;
  font-size: 1.25rem;
  font-weight: 800;
}

.dark .feature-title { color: #eee; }

.feature-desc {
  color: #777;
  font-size: 0.95rem;
  line-height: 1.6;
}

.premium-cta-btn {
  background: linear-gradient(135deg, #ff5000, #ff8000) !important;
  box-shadow: 0 10px 20px -5px rgba(255, 80, 0, 40%) !important;
  color: white !important;
  font-weight: 800;
  letter-spacing: 0.5px;
  transition: all 0.3s ease;

  &:hover {
    box-shadow: 0 15px 30px -5px rgba(255, 80, 0, 50%) !important;
    transform: scale(1.05);
  }
}

@media (max-width: 600px) {
  .section-headline { font-size: 1.75rem; }
}
</style>

