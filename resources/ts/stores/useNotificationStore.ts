import { defineStore } from 'pinia'

interface Notification {
  message: string
  color: 'success' | 'error' | 'warning' | 'info' | string
  isVisible: boolean
}

export const useNotificationStore = defineStore('notification', {
  state: (): Notification => ({
    message: '',
    color: 'success',
    isVisible: false,
  }),
  actions: {
    notify(message: string, color: 'success' | 'error' | 'warning' | 'info' | string = 'success') {
      this.message = message
      this.color = color
      this.isVisible = true

      setTimeout(() => {
        this.isVisible = false
      }, 5000)
    },
    success(message: string) {
      this.notify(message, 'success')
    },
    error(message: string) {
      this.notify(message, 'error')
    },
    warning(message: string) {
      this.notify(message, 'warning')
    },
    info(message: string) {
      this.notify(message, 'info')
    },
  },
})
