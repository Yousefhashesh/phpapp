<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import heroForkliftImg from '@images/front-pages/landing-page/hero-forklift.png'
import { useTheme } from 'vuetify'

const theme = useTheme()

const activeTab = ref('trace')
const trackingID = ref('')
const trackingResult = ref<any>(null)
const isTracking = ref(false)
const trackingError = ref('')

const trackNow = async () => {
  if (!trackingID.value) return
  
  isTracking.value = true
  trackingError.value = ''
  trackingResult.value = null
  
  try {
    const { data, error } = await useApi(`/orders/track/${trackingID.value}`).get().json()
    if (error.value) {
      trackingError.value = 'الشحنة غير موجودة أو كود غير صحيح'
    } else {
      trackingResult.value = data.value
    }
  } catch (e) {
    trackingError.value = 'حدث خطأ أثناء التتبع'
  } finally {
    isTracking.value = false
  }
}

const services = [
  { title: 'خدمات المستودعات', icon: 'tabler-home', color: '#6366f1' },
  { title: 'الشحن البحري', icon: 'tabler-waves-ocean', color: '#0ea5e9' },
  { title: 'الشحن الجوي', icon: 'tabler-plane-tilt', color: '#f43f5e' },
  { title: 'الشحن البري', icon: 'tabler-truck', color: '#f59e0b' },
]

const statusColors: any = {
  OUT_FOR_DELIVERY: 'primary',
  DELIVERED: 'success',
  HOLD: 'warning',
  UNDELIVERED: 'error',
}
</script>

<template>
  <div id="home" class="hero-wrapper">
    <!-- Animated Background Blobs -->
    <div class="blob-container">
      <div class="blob blob-1"></div>
      <div class="blob blob-2"></div>
      <div class="blob blob-3"></div>
    </div>

    <div id="landingHero" class="position-relative">
      <div class="landing-hero py-16">
        <VContainer>
          <VRow align="center">
            <!-- Left Side: Hero Text and Tracker -->
            <VCol cols="12" md="6" class="px-6 hero-content-col">
              <div class="glass-container pa-8 rounded-xl shadow-2xl">
                <h1 class="hero-title mb-4">
                  <span class="text-gradient">دقة الخدمات اللوجستية</span>
                </h1>
                <h2 class="hero-headline mb-6">
                  حيث تلتقي الكفاءة بالخبرة
                </h2>
                <p class="hero-description mb-10">
                  من التخليص الجمركي إلى تسليم الميل الأخير، نحن نهتم بكل التفاصيل لضمان وصول شحناتك في أمان تام وبأعلى سرعة.
                </p>

                <!-- Tracking Box (Glassmorphic) -->
                <div class="tracker-glass pa-1 rounded-xl">
                  <div class="d-flex align-center mb-0 tab-wrapper">
                    <div 
                      class="tab-btn py-3 px-6 cursor-pointer flex-fill text-center font-weight-bold transition-all"
                      :class="activeTab === 'trace' ? 'active-glass-tab' : 'inactive-glass-tab'"
                      @click="activeTab = 'trace'"
                    >
                      <VIcon icon="tabler-map-pin" class="me-2" size="20" />
                      تتبع الشحنة
                    </div>
                    <div 
                      class="tab-btn py-3 px-6 cursor-pointer flex-fill text-center font-weight-bold transition-all"
                      :class="activeTab === 'rates' ? 'active-glass-tab' : 'inactive-glass-tab'"
                      @click="activeTab = 'rates'"
                    >
                      <VIcon icon="tabler-currency-dollar" class="me-2" size="20" />
                      المخطط السعري
                    </div>
                  </div>
                  
                  <div class="pa-6 content-area">
                    <div v-if="activeTab === 'trace'">
                      <VTextField
                        v-model="trackingID"
                        placeholder="أدخل رقم الشحنة هنا..."
                        variant="plain"
                        class="glass-input mb-6"
                        hide-details
                        @keyup.enter="trackNow"
                      >
                        <template #prepend-inner>
                           <VIcon icon="tabler-search" class="text-disabled me-2" />
                        </template>
                      </VTextField>
                      
                      <VBtn 
                        block 
                        elevation="0"
                        size="x-large" 
                        class="premium-btn"
                        :loading="isTracking"
                        @click="trackNow"
                      >
                        إبدأ التتبع الآن
                      </VBtn>

                      <!-- Tracking Result -->
                      <VExpandTransition>
                        <div v-if="trackingResult" class="mt-8 pa-6 rounded-xl result-glass">
                          <div class="d-flex justify-space-between align-center mb-4">
                            <span class="tracking-code">#{{ trackingResult.code }}</span>
                            <VChip 
                              :color="statusColors[trackingResult.status] || 'secondary'" 
                              variant="elevated"
                              size="small"
                              class="font-weight-bold px-4"
                            >
                              {{ trackingResult.status === 'OUT_FOR_DELIVERY' ? 'خرج للتوصيل' : 
                                 trackingResult.status === 'DELIVERED' ? 'تم التسليم' :
                                 trackingResult.status === 'HOLD' ? 'قيد الانتظار' :
                                 trackingResult.status === 'UNDELIVERED' ? 'فشل التوصيل' : trackingResult.status }}
                            </VChip>
                          </div>
                          
                          <div class="d-flex flex-column gap-y-3">
                            <div class="d-flex align-center gap-x-2 text-body-2 mb-2">
                              <VIcon icon="tabler-user-check" size="18" color="primary" />
                              <span class="text-high-emphasis">{{ trackingResult.receiver_name }}</span>
                            </div>
                            <div class="d-flex align-center gap-x-2 text-body-2 mb-4">
                              <VIcon icon="tabler-map-pin-2" size="18" color="primary" />
                              <span class="text-disabled">{{ trackingResult.governorate }} - {{ trackingResult.city }}</span>
                            </div>
                          </div>
                          
                          <div class="timeline ps-4 border-s-2 border-primary-light">
                            <div v-for="(log, i) in trackingResult.history.slice(0, 3)" :key="i" class="mb-4 position-relative">
                              <div class="timeline-dot" />
                              <div class="text-caption font-weight-bold text-high-emphasis">{{ log.activity }}</div>
                              <div class="text-xs text-disabled">{{ log.created_at }}</div>
                            </div>
                          </div>
                        </div>
                      </VExpandTransition>

                      <div v-if="trackingError" class="mt-4 text-error text-center text-caption font-weight-bold px-4 py-2 rounded-lg bg-error-lighten-5">
                         <VIcon icon="tabler-alert-circle" start size="16" /> {{ trackingError }}
                      </div>
                    </div>
                    <div v-else class="text-center py-8">
                       <VIcon icon="tabler-lock-square-rounded" size="48" color="disabled" class="mb-4 opacity-50" />
                       <p class="text-body-1 text-disabled mb-6">قم بتسجيل الدخول لمشاهدة خطط الأسعار المتاحة لعملاء النظام.</p>
                       <VBtn variant="tonal" class="rounded-pill" :to="{ name: 'pages-authentication-login-v1' }">تسجيل الدخول</VBtn>
                    </div>
                  </div>
                </div>
              </div>
            </VCol>

            <!-- Right Side: Hero Image Container with Glass Effects -->
            <VCol cols="12" md="6" class="text-center position-relative d-none d-md-flex justify-center align-center">
               <div class="image-glass-effect">
                 <img
                    :src="heroForkliftImg"
                    alt="Logistic Forklift"
                    class="hero-main-img floating"
                  >
               </div>
            </VCol>
          </VRow>
        </VContainer>
      </div>
    </div>

    <!-- Services Section (Liquid Glass Cards) -->
    <VContainer class="services-container py-16 mt-n16 position-relative" style="z-index: 10;">
      <VRow justify="center">
        <VCol v-for="service in services" :key="service.title" cols="12" sm="6" md="3">
          <div class="glass-service-card pa-8 text-center transition-all">
            <div class="icon-blob mb-6" :style="{ backgroundColor: service.color + '20' }">
              <VIcon :icon="service.icon" size="32" :style="{ color: service.color }" />
            </div>
            <div class="font-weight-black text-h6 mb-2">{{ service.title }}</div>
            <div class="text-caption text-disabled">خدمات شحن متكاملة بأعلى المعايير العالمية في الدقة والأمان.</div>
          </div>
        </VCol>
      </VRow>
    </VContainer>
  </div>
</template>

<style lang="scss" scoped>
@import "https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Cairo:wght@400;700;900&display=swap";

.hero-wrapper {
  position: relative;
  overflow: hidden;
  background:
    radial-gradient(circle at top right, rgba(var(--v-theme-primary), 0.05), transparent 40%),
    radial-gradient(circle at bottom left, rgba(var(--v-theme-primary), 0.03), transparent 40%);
  font-family: Cairo, Outfit, sans-serif;
  min-block-size: 100vh;
}

/* Animated Blobs */
.blob-container {
  position: absolute;
  z-index: 0;
  overflow: hidden;
  filter: blur(80px);
  inset: 0;
}

.blob {
  position: absolute;
  border-radius: 50%;
  animation: float 20s infinite alternate;
  opacity: 0.4;
}

.blob-1 {
  animation-duration: 25s;
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)), #ffd600);
  block-size: 500px;
  inline-size: 500px;
  inset-block-start: -100px;
  inset-inline-end: -100px;
}

.blob-2 {
  animation-delay: -5s;
  animation-duration: 30s;
  background: linear-gradient(135deg, #0077b6, #00b4d8);
  block-size: 400px;
  inline-size: 400px;
  inset-block-end: 10%;
  inset-inline-start: -50px;
}

.blob-3 {
  animation-delay: -10s;
  animation-duration: 22s;
  background: linear-gradient(135deg, #6366f1, #a855f7);
  block-size: 300px;
  inline-size: 300px;
  inset-block-start: 40%;
  inset-inline-end: 20%;
}

@keyframes float {
  from { transform: translate(0, 0) scale(1); }
  to { transform: translate(100px, 50px) scale(1.2); }
}

.landing-hero {
  position: relative;
  z-index: 1;
}

/* Glassmorphism Classes */
.glass-container {
  border: 1px solid rgba(255, 255, 255, 30%);
  backdrop-filter: blur(20px) saturate(180%);
  background: rgba(255, 255, 255, 70%);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 10%);
}

.dark .glass-container {
  border: 1px solid rgba(255, 255, 255, 10%);
  background: rgba(15, 23, 42, 60%);
}

.text-gradient {
  background: linear-gradient(to right, rgb(var(--v-theme-primary)), #ff9500);
  background-clip: text;
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: 2px;
  -webkit-text-fill-color: transparent;
}

.hero-headline {
  color: #1a1a1a;
  font-size: 2.75rem;
  font-weight: 900;
  line-height: 1.25;
}

.dark .hero-headline { color: white; }

.hero-description {
  color: #666;
  font-size: 1.1rem;
  line-height: 1.6;
}

/* Tracker Glass */
.tracker-glass {
  border: 1px solid rgba(255, 255, 255, 20%);
  background: rgba(255, 255, 255, 40%);

  .tab-wrapper {
    padding: 4px;
    border-radius: 12px;
    background: rgba(0, 0, 0, 3%);
  }
}

.tab-btn {
  border-radius: 10px;
  font-size: 0.9rem;

  &.active-glass-tab {
    background: white;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 10%);
    color: rgb(var(--v-theme-primary));
  }

  &.inactive-glass-tab {
    color: #888;
    &:hover { background: rgba(255, 255, 255, 20%); }
  }
}

.glass-input {
  border: 1px solid rgba(0, 0, 0, 5%);
  border-radius: 12px;
  background: rgba(255, 255, 255, 50%);
  padding-block: 8px;
  padding-inline: 16px;
  transition: all 0.3s ease;

  &:focus-within {
    border-color: rgb(var(--v-theme-primary));
    background: white;
    box-shadow: 0 0 0 4px rgba(var(--v-theme-primary), 10%);
  }
}

.premium-btn {
  border-radius: 12px;
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)), #ff8000) !important;
  box-shadow: 0 10px 20px -5px rgba(var(--v-theme-primary), 40%);
  color: white !important;
  font-weight: 800;
  letter-spacing: 0.5px;
  text-transform: none;
  transition: all 0.3s ease;

  &:hover {
    box-shadow: 0 15px 25px -5px rgba(255, 80, 0, 50%);
    transform: translateY(-2px);
  }
}

/* Results Glass */
.result-glass {
  border: 1px solid rgba(var(--v-theme-primary), 10%);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 60%);
}

.tracking-code {
  color: #1e293b;
  font-size: 1.5rem;
  font-weight: 900;
}

.timeline-dot {
  position: absolute;
  border: 2px solid white;
  border-radius: 50%;
  background: rgb(var(--v-theme-primary));
  block-size: 10px;
  box-shadow: 0 0 0 4px rgba(var(--v-theme-primary), 10%);
  inline-size: 10px;
  inset-block-start: 6px;
  inset-inline-start: -21px;
}

/* Service Card Glass */
.glass-service-card {
  border: 1px solid rgba(255, 255, 255, 30%);
  border-radius: 24px;
  backdrop-filter: blur(15px);
  background: rgba(255, 255, 255, 45%);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 3%);

  &:hover {
    border-color: rgba(var(--v-theme-primary), 0.2);
    background: rgba(255, 255, 255, 70%);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 6%);
    transform: translateY(-10px);
  }
}

.dark .glass-service-card {
  border: 1px solid rgba(255, 255, 255, 5%);
  background: rgba(30, 41, 59, 40%);
}

.icon-blob {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
  animation: blob-shape 8s infinite alternate;
  block-size: 70px;
  inline-size: 70px;
  margin-block: 0;
  margin-inline: auto;
}

@keyframes blob-shape {
  0% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
  100% { border-radius: 50% 50% 20% 80% / 25% 80% 20% 75%; }
}

.hero-main-img {
  filter: drop-shadow(0 20px 50px rgba(0, 0, 0, 15%));
  inline-size: 100%;
  max-inline-size: 600px;
}

.floating {
  animation: floating-anim 6s ease-in-out infinite;
}

@keyframes floating-anim {
  0% { transform: translateY(0); }
  50% { transform: translateY(-30px); }
  100% { transform: translateY(0); }
}

.image-glass-effect {
  position: relative;

  &::before {
    position: absolute;
    z-index: -1;
    background: radial-gradient(circle, rgba(var(--v-theme-primary), 0.1), transparent 70%);
    block-size: 120%;
    content: "";
    inline-size: 120%;
    inset-block-start: -10%;
    inset-inline-start: -10%;
  }
}

@media (max-width: 959px) {
  .hero-headline { font-size: 2rem; }
  .glass-container { padding: 1.5rem !important; }
}
</style>

