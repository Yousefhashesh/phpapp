<script setup lang="ts">
import { useApi } from '@/composables/useApi';
const { data } = await useApi<any>('/landing-page')
const site = computed(() => data.value?.site ?? { name: 'Shipya', logo: '', email: '', phone: '', address: '' })

const pagesList = [
  { name: 'الرئيسية', to: { name: 'root' } },
  { name: 'المميزات', to: { name: 'root', hash: '#features' } },
  { name: 'فريقنا', to: { name: 'root', hash: '#team' } },
  { name: 'الأسئلة الشائعة', to: { name: 'root', hash: '#faq-section' } },
]
</script>

<template>
  <div class="footer-wrapper">
    <div class="footer-glass pt-16 pb-12">
      <VContainer>
        <VRow>
          <VCol cols="12" md="5" class="pe-md-16">
            <div class="mb-8">
              <div class="app-logo mb-6 align-center d-flex gap-x-3">
                <VAvatar v-if="site.logo" :image="site.logo" size="40" class="logo-shadow" />
                <VIcon v-else icon="tabler-truck-delivery" size="40" color="primary" />
                <h1 class="nav-logo-title font-weight-black">
                  {{ site.name }}
                </h1>
              </div>
              <p class="mb-8 footer-desc">
                المنصة الرائدة في إدارة اللوجستيات والشحن في المنطقة. نقدم حلولاً تقنية متكاملة لربط التجار والمناديب والعملاء في بيئة عمل ذكية واحترافية.
              </p>
              <div class="d-flex gap-x-4">
                 <div class="social-btn"><VIcon icon="tabler-brand-facebook" /></div>
                 <div class="social-btn"><VIcon icon="tabler-brand-x" /></div>
                 <div class="social-btn"><VIcon icon="tabler-brand-instagram" /></div>
                 <div class="social-btn"><VIcon icon="tabler-brand-linkedin" /></div>
              </div>
            </div>
          </VCol>

          <VCol cols="6" md="3">
            <h6 class="footer-title mb-6">روابط سريعة</h6>
            <ul class="footer-links">
              <li v-for="link in pagesList" :key="link.name" class="mb-4">
                <RouterLink :to="link.to" class="footer-link-item">
                  <VIcon icon="tabler-chevron-left" size="14" class="me-2 icon-arrow" />
                  {{ link.name }}
                </RouterLink>
              </li>
            </ul>
          </VCol>

          <VCol cols="6" md="4">
            <h6 class="footer-title mb-6">معلومات التواصل</h6>
            <div class="d-flex flex-column gap-y-5">
              <div class="d-flex align-center gap-x-4 contact-item" v-if="site.email">
                <div class="contact-icon"><VIcon icon="tabler-mail" size="18" /></div>
                <div>
                   <div class="text-caption text-disabled">البريد الإلكتروني</div>
                   <div class="text-body-2 font-weight-bold">{{ site.email }}</div>
                </div>
              </div>
              <div class="d-flex align-center gap-x-4 contact-item" v-if="site.phone">
                <div class="contact-icon"><VIcon icon="tabler-phone" size="18" /></div>
                <div>
                   <div class="text-caption text-disabled">رقم الهاتف</div>
                   <div class="text-body-2 font-weight-bold" dir="ltr">{{ site.phone }}</div>
                </div>
              </div>
            </div>
          </VCol>
        </VRow>
      </VContainer>
    </div>

    <div class="footer-bottom py-6">
      <VContainer>
        <div class="d-flex justify-space-between align-center flex-wrap gap-4">
          <div class="text-body-2 opacity-75 font-weight-medium">
            &copy; {{ new Date().getFullYear() }} {{ site.name }}. جميع الحقوق محفوظة لشركة شيبيا للخدمات اللوجستية.
          </div>
          <div class="d-flex align-center gap-x-2">
            <span class="text-caption opacity-50">صنع بكل ❤️ في مصر</span>
          </div>
        </div>
      </VContainer>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import "https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Cairo:wght@400;700;900&display=swap";

.footer-wrapper {
  background: #f8fafc;
  font-family: Cairo, Outfit, sans-serif;
}

.dark .footer-wrapper { background: #0f172a; }

.footer-glass {
  border-radius: 80px 80px 0 0;
  background: white;
  border-block-start: 1px solid rgba(0, 0, 0, 5%);
  box-shadow: 0 -20px 40px rgba(0, 0, 0, 2%);
}

.dark .footer-glass {
  backdrop-filter: blur(20px);
  background: rgba(30, 41, 59, 50%);
  border-block-start: 1px solid rgba(255, 255, 255, 5%);
}

.nav-logo-title {
  background: linear-gradient(135deg, #ff5c00, #ffd600);
  background-clip: text;
  font-size: 1.75rem;
  -webkit-text-fill-color: transparent;
}

.footer-desc {
  color: #64748b;
  font-size: 1rem;
  line-height: 1.8;
}

.footer-title {
  position: relative;
  color: #1e293b;
  font-size: 1.1rem;
  font-weight: 800;
  padding-block-end: 12px;

  &::after {
    position: absolute;
    border-radius: 10px;
    background: #ff5c00;
    block-size: 3px;
    content: "";
    inline-size: 40px;
    inset-block-end: 0;
    inset-inline-end: 0;
  }
}

.dark .footer-title { color: white; }

.footer-links {
  padding: 0;
  list-style: none;
}

.footer-link-item {
  display: flex;
  align-items: center;
  color: #64748b;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;

  &:hover {
    color: #ff5c00;
    transform: translateX(-5px);
    .icon-arrow { opacity: 1; }
  }

  .icon-arrow { opacity: 0; transition: opacity 0.3s; }
}

.dark .footer-link-item { color: #94a3b8; &:hover { color: white; } }

.contact-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: rgba(255, 92, 0, 10%);
  block-size: 40px;
  color: #ff5c00;
  inline-size: 40px;
}

.social-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: #f1f5f9;
  block-size: 44px;
  color: #64748b;
  cursor: pointer;
  inline-size: 44px;
  transition: all 0.3s ease;

  &:hover {
    background: #ff5c00;
    color: white;
    transform: translateY(-3px);
  }
}

.dark .social-btn { background: #1e293b; color: #94a3b8; }

.footer-bottom {
  background: #ff5c00;
  color: white;
}

.logo-shadow { filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 10%)); }
</style>

