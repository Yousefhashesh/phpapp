import { useApi } from '@/composables/useApi'
import { layoutConfig } from '@themeConfig'
import { defineStore } from 'pinia'
import { h, ref } from 'vue'

export const useSettingsStore = defineStore('settings', () => {
  const appTitle = ref(layoutConfig.app.title)
  const siteLogo = ref('')
  
  // Colors state
  const primaryLight = ref('')
  const primaryDark = ref('')

  const fetchSettings = async () => {
    try {
      const { data } = await useApi('/app-config').get().json()
      if (data.value) {
        // Handle Site Name
        if (data.value.site_identity?.site_name) {
          const siteName = data.value.site_identity.site_name
          appTitle.value = siteName
          layoutConfig.app.title = siteName
          if (typeof document !== 'undefined') {
            document.title = siteName
          }
        }

        // Handle Site Icon (Favicon)
        if (data.value.site_logos?.site_logo_32_light) {
          const iconUrl = data.value.site_logos.site_logo_32_light
          if (typeof document !== 'undefined') {
             let link = document.querySelector("link[rel~='icon']") as HTMLLinkElement;
             if (!link) {
               link = document.createElement('link');
               link.rel = 'icon';
               document.getElementsByTagName('head')[0].appendChild(link);
             }
             link.href = iconUrl;
          }
        }

        // Handle Site Logos
        if (data.value.site_logos?.site_logo_512_light) {
           layoutConfig.app.logo = h('img', { 
            src: data.value.site_logos.site_logo_512_light,
            style: 'height: 32px; width: auto;' 
           })
        }

        // Handle Primary Colors - Just store them
        if (data.value.site_theme?.site_color_primary_light) {
          primaryLight.value = data.value.site_theme.site_color_primary_light
        }
        if (data.value.site_theme?.site_color_primary_dark) {
          primaryDark.value = data.value.site_theme.site_color_primary_dark
        }
      }
    } catch (error) {
      console.error('Failed to fetch app settings:', error)
    }
  }

  return {
    appTitle,
    siteLogo,
    primaryLight,
    primaryDark,
    fetchSettings,
  }
})
