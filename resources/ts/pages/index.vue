<script setup lang="ts">
import Footer from '@/views/front-pages/front-page-footer.vue'
import Navbar from '@/views/front-pages/front-page-navbar.vue'
import CustomersReview from '@/views/front-pages/landing-page/customers-review.vue'
import FaqSection from '@/views/front-pages/landing-page/faq-section.vue'
import Features from '@/views/front-pages/landing-page/features.vue'
import HeroSection from '@/views/front-pages/landing-page/hero-section.vue'
import PricingPlans from '@/views/front-pages/landing-page/pricing-plans.vue'

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: false,
    public: true,
  },
})
</script>

<template>
  <div class="landing-page">
    <Suspense>
      <Navbar />
      <template #fallback>
        <div class="pa-4 text-center opacity-0">Loading Navbar...</div>
      </template>
    </Suspense>
    
    <Suspense>
      <HeroSection />
    </Suspense>
    
    <Features />
    
    <Suspense>
      <CustomersReview />
    </Suspense>
    
    <Suspense>
      <PricingPlans />
    </Suspense>
    
    <Suspense>
      <FaqSection />
    </Suspense>
    
    <Suspense>
      <Footer />
    </Suspense>
  </div>
</template>

<style lang="scss">
html {
  scroll-behavior: smooth;
}

.landing-page {
  animation: fade-in 0.8s ease-out;
}

@keyframes fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* Smooth reveal animation for sections */
.landing-page > * {
  view-transition-name: section;
}

@media (max-width: 960px) {
  .v-container {
    padding-inline: 1.5rem !important;
  }
}

/* Premium Scrollbar */
::-webkit-scrollbar {
  inline-size: 10px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  border: 3px solid transparent;
  border-radius: 10px;
  background: rgba(var(--v-theme-primary), 0.2);
  background-clip: content-box;
}

::-webkit-scrollbar-thumb:hover {
  border: 3px solid transparent;
  background: rgba(var(--v-theme-primary), 0.5);
  background-clip: content-box;
}
</style>
