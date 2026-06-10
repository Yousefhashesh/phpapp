<script setup lang="ts">
import AppNotification from '@core/components/AppNotification.vue'
import ScrollToTop from '@core/components/ScrollToTop.vue'
import initCore from '@core/initCore'
import { initConfigStore, useConfigStore } from '@core/stores/config'
import { useSettingsStore } from '@core/stores/settings'
import { hexToRgb } from '@core/utils/colorConverter'
import { useTheme } from 'vuetify'

const { global, themes } = useTheme()

// ℹ️ Sync current theme with initial loader theme
initCore()
initConfigStore()

const configStore = useConfigStore()
const settingsStore = useSettingsStore()

// Watch for primary color changes from database/settings
watch(() => settingsStore.primaryLight, (val) => {
  if (val) {
    themes.value.light.colors.primary = val
    themes.value.light.colors['primary-darken-1'] = val // Simple fallback
  }
}, { immediate: true })

watch(() => settingsStore.primaryDark, (val) => {
  if (val) {
    themes.value.dark.colors.primary = val
    themes.value.dark.colors['primary-darken-1'] = val // Simple fallback
  }
}, { immediate: true })
</script>

<template>
  <VLocaleProvider :rtl="configStore.isAppRTL">
    <!-- ℹ️ This is required to set the background color of active nav link based on currently active global theme's primary -->
    <VApp :style="`--v-global-theme-primary: ${hexToRgb(global.current.value.colors.primary)}`">
      <RouterView />
      <ScrollToTop />
      <AppNotification />
    </VApp>
  </VLocaleProvider>
</template>
