<script setup lang="ts">
interface GroupedPrice {
  price: number
  governorates: string[]
}

interface Plan {
  id: number
  name: string
  order_count: number
  grouped_prices: GroupedPrice[]
}

const { data } = await useApi<any>('/landing-page')
const plans = computed<Plan[]>(() => data.value?.plans ?? [])

const getPlanIcon = (index: number) => {
  if (index === 0) return 'tabler-at'
  if (index === 1) return 'tabler-plane'
  return 'tabler-rocket'
}

const getPlanColor = (index: number) => {
  if (index === 0) return '#0ea5e9'
  if (index === 1) return 'rgb(var(--v-theme-primary))'
  return '#8b5cf6'
}
</script>

<template>
  <div id="pricing-plan" class="pricing-wrapper py-16">
    <VContainer>
      <div class="headers d-flex justify-center flex-column align-center mb-12">
        <VChip variant="tonal" color="primary" class="mb-4">خطط الشحن</VChip>
        <h2 class="section-headline text-center mb-4">تغطية واسعة وأسعار مرنة تناسبك</h2>
        <p class="section-subline text-center">اختر الخطة التي تخدم احتياجاتك اللوجستية بأفضل تكلفة.</p>
      </div>

      <VRow class="justify-center">
        <VCol
          v-for="(plan, index) in plans"
          :key="plan.id"
          cols="12"
          md="4"
          sm="6"
        >
          <div class="glass-pricing-card pa-8 h-100 d-flex flex-column" :class="{ 'featured': index === 1 }">
             <div class="d-flex justify-space-between align-center mb-8">
               <div class="plan-icon-box" :style="{ background: getPlanColor(index) + '15', color: getPlanColor(index) }">
                 <VIcon :icon="getPlanIcon(index)" size="32" />
               </div>
               <div v-if="index === 1" class="popular-tag">الأكثر طلباً ✨</div>
             </div>

             <h4 class="plan-name mb-2">{{ plan.name }}</h4>
             <div class="plan-detail mb-6">دعم {{ plan.order_count }} أوردر شهرياً</div>
             
             <VDivider class="mb-6 opacity-10" />

             <div class="flex-grow-1 mb-8">
               <div v-for="group in plan.grouped_prices" :key="group.price" class="pricing-group mb-4">
                 <div class="d-flex justify-space-between align-center mb-1">
                   <span class="price-value">{{ group.price }} <small>ج.م</small></span>
                   <span class="price-label">سعر الشحن</span>
                 </div>
                 <div class="governorates-list">{{ group.governorates.join(' • ') }}</div>
               </div>
             </div>

             <VBtn
                block
                size="large"
                :color="index === 1 ? 'primary' : 'secondary'"
                variant="flat"
                class="rounded-pill font-weight-bold cta-btn"
                :to="{ name: 'pages-authentication-login-v1' }"
             >
               اشترك الآن
             </VBtn>
          </div>
        </VCol>
      </VRow>
    </VContainer>
  </div>
</template>

<style lang="scss" scoped>
@import "https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Cairo:wght@400;700;900&display=swap";

.pricing-wrapper {
  background: white;
  font-family: Cairo, Outfit, sans-serif;
}

.dark .pricing-wrapper { background: #0f172a; }

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

.glass-pricing-card {
  border: 1px solid rgba(0, 0, 0, 5%);
  border-radius: 32px;
  backdrop-filter: blur(20px) saturate(180%);
  background: rgba(255, 255, 255, 40%);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

  &:hover {
    border-color: rgba(var(--v-theme-primary), 0.3);
    box-shadow: 0 30px 60px -20px rgba(0, 0, 0, 10%);
    transform: translateY(-10px);
  }

  &.featured {
    z-index: 2;
    border: 2px solid #ff5c00;
    background: rgba(255, 255, 255, 70%);
    transform: scale(1.05);
  }
}

.dark .glass-pricing-card {
  border: 1px solid rgba(255, 255, 255, 5%);
  background: rgba(30, 41, 59, 30%);
  &.featured { background: rgba(30, 41, 59, 50%); }
}

.plan-icon-box {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 16px;
  background: rgba(var(--v-theme-primary), 0.1) !important;
  block-size: 56px;
  color: rgb(var(--v-theme-primary)) !important;
  inline-size: 56px;
}

.popular-tag {
  border-radius: 20px;
  background: rgb(var(--v-theme-primary));
  color: white;
  font-size: 0.75rem;
  font-weight: bold;
  padding-block: 4px;
  padding-inline: 12px;
}

.plan-name { font-size: 1.5rem; font-weight: 800; }
.plan-detail { color: #64748b; font-size: 0.95rem; font-weight: 600; }

.price-value { color: rgb(var(--v-theme-primary)); font-size: 1.5rem; font-weight: 900; }
.price-label { color: #94a3b8; font-size: 0.75rem; font-weight: bold; }
.governorates-list { color: #64748b; font-size: 0.85rem; line-height: 1.5; }

.cta-btn {
  block-size: 52px !important;
}
</style>

