if (typeof window !== 'undefined' && 'serviceWorker' in navigator) {
  navigator.serviceWorker.getRegistrations().then(registrations => {
    for (const registration of registrations) {
      registration.unregister()
    }
  })
}

import { createApp } from 'vue'

import App from '@/App.vue'
import { store } from '@/plugins/2.pinia'
import { registerPlugins } from '@core/utils/plugins'

// Styles
import '@core-scss/template/index.scss'
import '@styles/styles.scss'

// Create vue app
const app = createApp(App)

// Register plugins
registerPlugins(app)

// Initializing the stores with configuration data from Settings
import { useSettingsStore } from '@core/stores/settings'
const settingsStore = useSettingsStore(store)
await settingsStore.fetchSettings()

// 👉 Override window.alert to use our global notification system
import { useNotificationStore } from '@/stores/useNotificationStore'
const notificationStore = useNotificationStore(store)

if (typeof window !== 'undefined') {
  window.alert = (message: string) => {
    const lower = String(message).toLowerCase()
    
    // Simple heuristic for colors
    const isSuccess = ['success', 'تم ', 'بنجاح', 'successfully'].some(k => lower.includes(k))
    const isError = ['error', 'خطأ', 'فشل', 'failed'].some(k => lower.includes(k))
    const isWarning = ['warning', 'تحذير', 'تنبيه'].some(k => lower.includes(k))

    notificationStore.notify(
      message, 
      isError ? 'error' : (isWarning ? 'warning' : (isSuccess ? 'success' : 'info'))
    )
  }
}

// Mount vue app
app.mount('#app')
