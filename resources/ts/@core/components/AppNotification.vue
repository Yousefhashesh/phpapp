<script setup lang="ts">
import { useNotificationStore } from '@/stores/useNotificationStore';

const store = useNotificationStore()
</script>

<template>
  <VSnackbar
    v-model="store.isVisible"
    :color="store.color"
    location="top end"
    :timeout="5000"
  >
    <VAlert
      :type="store.color as any"
      variant="elevated"
      density="compact"
      class="ma-0 font-weight-bold"
      :text="store.message"
    />
  </VSnackbar>
</template>
