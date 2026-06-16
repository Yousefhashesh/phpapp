<script setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useDisplay, useTheme } from 'vuetify'

import NavbarThemeSwitcher from '@/layouts/components/NavbarThemeSwitcher.vue'

const { data } = await useApi<any>('/landing-page')
const site = computed(() => data.value?.site ?? { name: 'Shipya', logo: '' })

const display = useDisplay()
const theme = useTheme()
const { y } = useWindowScroll()
const route = useRoute()
const router = useRouter()
const ability = useAbility()
const sidebar = ref(false)

const accessToken = useCookie<string | null>('accessToken')
const userData = useCookie<any>('userData')
const isLoggedIn = computed(() => !!(accessToken.value && userData.value))

const logout = async () => {
  try {
    await $api('/logout', { method: 'POST' })
  }
  catch {
    // proceed with client-side cleanup even if the request fails
  }

  accessToken.value = null
  userData.value = null
  useLocalStorage('userAbilityRules', []).value = null
  ability.update([])

  await router.push({ name: 'root' })
}

watch(() => display, () => {
  return display.mdAndUp ? sidebar.value = false : sidebar.value
}, { deep: true })
</script>

<template>
  <!--    Navigation drawer for mobile devices  -->
  <VNavigationDrawer
    v-model="sidebar"
    width="275"
    data-allow-mismatch
    disable-resize-watcher
    class="sidebar-glass"
  >
    <PerfectScrollbar
      :options="{ wheelPropagation: false }"
      class="h-100"
    >
      <div class="pa-6">
        <div class="d-flex align-center gap-x-3 mb-10">
          <VAvatar v-if="site.logo" :image="site.logo" size="40" class="logo-shadow" />
          <VIcon v-else icon="tabler-truck" size="32" color="primary" />
          <h1 class="text-h1 font-weight-black gradient-text" style="font-size: 1.5rem;">{{ site.name }}</h1>
        </div>

        <div class="d-flex flex-column gap-y-6">
          <RouterLink
            v-for="(item, index) in [{t: 'الرئيسية', h: 'home'}, {t: 'المميزات', h: 'features'}, {t: 'الأسئلة الشائعة', h: 'faq-section'}]"
            :key="index"
            :to="{ name: 'root', hash: `#${item.h}` }"
            class="mobile-nav-link"
          >
            {{ item.t }}
          </RouterLink>

          <template v-if="isLoggedIn">
            <VBtn
              block
              color="primary"
              elevation="0"
              size="large"
              :to="{ name: 'dashboards-orders' }"
              class="mt-6 premium-btn"
            >
              لوحة التحكم
            </VBtn>
            <VBtn
              block
              variant="outlined"
              color="error"
              size="large"
              prepend-icon="tabler-logout"
              class="mt-3"
              @click="logout"
            >
              تسجيل الخروج
            </VBtn>
          </template>
          <VBtn
            v-else
            block
            color="primary"
            elevation="0"
            size="large"
            :to="{ name: 'pages-authentication-login-v1' }"
            class="mt-6 premium-btn"
          >
            دخول / تسجيل جديد
          </VBtn>
        </div>
      </div>

      <!-- Navigation drawer close icon -->
      <VIcon
        id="navigation-drawer-close-btn"
        icon="tabler-x"
        size="24"
        class="ma-4"
        @click="sidebar = !sidebar"
      />
    </PerfectScrollbar>
  </VNavigationDrawer>

  <!--    Navbar for desktop devices  -->
  <div class="navbar-wrapper" :class="{ 'scrolled': y > 20 }">
    <div class="container-narrow">
      <nav class="glass-navbar">
        <!-- toggle icon for mobile device -->
        <IconBtn
          id="vertical-nav-toggle-btn"
          class="ms-n3 me-2 d-inline-block d-md-none"
          @click="sidebar = !sidebar"
        >
          <VIcon
            size="26"
            icon="tabler-menu-2"
          />
        </IconBtn>

        <!-- Logo and Branding -->
        <RouterLink
          :to="{ name: 'root' }"
          class="d-flex align-center gap-x-3 me-8 brand-link"
        >
          <VAvatar v-if="site.logo" :image="site.logo" size="32" class="logo-shadow" />
          <VIcon v-else icon="tabler-truck-delivery" size="32" color="primary" />
          <h1 class="nav-logo-title font-weight-black">
            {{ site.name }}
          </h1>
        </RouterLink>

        <!-- Navigation Links -->
        <div class="d-none d-md-flex align-center flex-grow-1 justify-center">
          <div class="nav-links-pill">
            <RouterLink
              v-for="(item, index) in [{t: 'الرئيسية', h: 'home'}, {t: 'الخدمات', h: 'features'}, {t: 'الدعم', h: 'faq-section'}]"
              :key="index"
              :to="{ name: 'root', hash: `#${item.h}` }"
              class="nav-item"
            >
              {{ item.t }}
            </RouterLink>
          </div>
        </div>

        <!-- Actions -->
        <div class="d-flex gap-x-4 align-center ms-auto">
          <div class="d-none d-sm-flex">
            <NavbarThemeSwitcher />
          </div>

          <template v-if="isLoggedIn">
            <VBtn
              variant="flat"
              color="primary"
              class="font-weight-bold rounded-pill px-6 login-btn"
              :to="{ name: 'dashboards-orders' }"
            >
              لوحة التحكم
            </VBtn>
            <IconBtn
              color="error"
              @click="logout"
            >
              <VIcon icon="tabler-logout" />
              <VTooltip
                activator="parent"
                location="bottom"
              >
                تسجيل الخروج
              </VTooltip>
            </IconBtn>
          </template>
          <VBtn
            v-else
            variant="flat"
            color="primary"
            class="font-weight-bold rounded-pill px-6 login-btn"
            :to="{ name: 'pages-authentication-login-v1' }"
          >
            ابدأ الآن
          </VBtn>
        </div>
      </nav>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import "https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Cairo:wght@400;700;900&display=swap";

.navbar-wrapper {
  position: fixed;
  z-index: 1000;
  font-family: Cairo, Outfit, sans-serif;
  inset-block-start: 0;
  inset-inline: 0;
  padding-block: 1.5rem;
  padding-inline: 1rem;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);

  &.scrolled {
    padding-block: 0.75rem;
    padding-inline: 1rem;
  }
}

.container-narrow {
  margin-block: 0;
  margin-inline: auto;
  max-inline-size: 1200px;
}

.glass-navbar {
  display: flex;
  align-items: center;
  border: 1px solid rgba(255, 255, 255, 40%);
  border-radius: 100px;
  backdrop-filter: blur(20px) saturate(180%);
  background: rgba(255, 255, 255, 70%);
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 10%);
  padding-block: 0.5rem;
  padding-inline: 1.5rem 1rem;
  transition: all 0.3s ease;
}

.dark .glass-navbar {
  border: 1px solid rgba(255, 255, 255, 10%);
  background: rgba(15, 23, 42, 60%);
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 30%);
}

.nav-logo-title {
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)), #ffd600);
  background-clip: text;
  font-size: 1.5rem;
  -webkit-text-fill-color: transparent;
}

.brand-link { text-decoration: none; }

/* Desktop Navigation Items */
.nav-links-pill {
  display: flex;
  padding: 0.25rem;
  border-radius: 50px;
  background: rgba(0, 0, 0, 5%);
  gap: 0.5rem;
}

.dark .nav-links-pill { background: rgba(255, 255, 255, 5%); }

.nav-item {
  border-radius: 50px;
  color: #444;
  font-size: 0.95rem;
  font-weight: 700;
  padding-block: 0.5rem;
  padding-inline: 1.25rem;
  text-decoration: none;
  transition: all 0.3s ease;

  &:hover {
    background: rgba(255, 255, 255, 60%);
    color: rgb(var(--v-theme-primary));
  }

  &.router-link-active {
    color: rgb(var(--v-theme-primary));
  }
}

.dark .nav-item {
  color: #ccc;

  &:hover {
    background: rgba(255, 255, 255, 10%);
    color: white;
  }
}

/* Sidebar Styling */
.sidebar-glass {
  backdrop-filter: blur(20px) !important;
  background: rgba(255, 255, 255, 80%) !important;
}

.dark .sidebar-glass {
  background: rgba(15, 23, 42, 90%) !important;
}

.mobile-nav-link {
  border-block-end: 2px solid transparent;
  color: #333;
  font-size: 1.25rem;
  font-weight: 800;
  padding-block: 0.5rem;
  padding-inline: 0;
  text-decoration: none;
  transition: border 0.3s;

  &:hover {
    border-color: rgb(var(--v-theme-primary));
    color: rgb(var(--v-theme-primary));
  }
}

.dark .mobile-nav-link { color: #eee; }

.logo-shadow { filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 10%)); }

.premium-btn {
  border-radius: 12px;
  background: linear-gradient(135deg, #ff5000, #ff8000) !important;
  color: white !important;
  font-weight: 900;
}

.login-btn {
  background: rgb(var(--v-theme-primary)) !important;
  box-shadow: 0 4px 15px rgba(var(--v-theme-primary), 30%) !important;
  color: white !important;
  &:hover { transform: scale(1.05); }
}
</style>

