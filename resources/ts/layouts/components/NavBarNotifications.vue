<script lang="ts" setup>
import type { Notification } from '@layouts/types'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
dayjs.extend(relativeTime)

// مثال للأيقونات حسب نوع الإشعار
const defaultIcon = 'tabler-bell'
const notificationIcons: Record<string, string> = {
  'App\\Notifications\\UserRegistered': 'tabler-user-plus',
  'App\\Notifications\\OrderReceived': 'tabler-package',
  'App\\Notifications\\PaymentReceived': 'tabler-credit-card',
}

import { useApi } from '@/composables/useApi'

const notifications = ref<Notification[]>([])

const fetchNotifications = async () => {
  try {
    const { data } = await useApi<any>('/notifications').get().json()
    if (data.value) {
      notifications.value = (data.value.data || data.value || []).map((item: any) => ({
        id: item.id,
        title: item.data?.title || item.type || 'Notification',
        subtitle: item.data?.body || item.data?.message || '',
        time: dayjs(item.created_at).fromNow(),
        isSeen: !!item.read_at,
        icon: notificationIcons[item.type] || defaultIcon,
      }))
    }
  } catch (error) {
    console.error('Notifications fetch error:', error)
  }
}

onMounted(fetchNotifications)

const removeNotification = (notificationId: number) => {
  notifications.value.forEach((item, index) => {
    if (notificationId === item.id)
      notifications.value.splice(index, 1)
  })
}

const markRead = (notificationId: number[]) => {
  notifications.value.forEach(item => {
    notificationId.forEach(id => {
      if (id === item.id)
        item.isSeen = true
    })
  })
}

const markUnRead = (notificationId: number[]) => {
  notifications.value.forEach(item => {
    notificationId.forEach(id => {
      if (id === item.id)
        item.isSeen = false
    })
  })
}

const handleNotificationClick = (notification: Notification) => {
  if (!notification.isSeen)
    markRead([notification.id])
}
</script>

<template>
  <Notifications
    :notifications="notifications"
    @remove="removeNotification"
    @read="markRead"
    @unread="markUnRead"
    @click:notification="handleNotificationClick"
  />
</template>
