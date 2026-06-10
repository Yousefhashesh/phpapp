<script setup lang="ts">
defineProps<{
  settings: Record<string, any> | null
  title: string
  subtitle?: string
  metaLines?: { label: string; value: string }[]
}>()
</script>

<template>
  <div class="print-doc-header d-flex justify-space-between align-start mb-6">
    <div class="d-flex align-center gap-3">
      <VAvatar
        v-if="settings?.site_logos?.site_logo_512_light"
        rounded
        size="80"
        class="me-2"
      >
        <VImg :src="settings.site_logos.site_logo_512_light" />
      </VAvatar>
      <VAvatar v-else rounded size="60" color="primary" variant="tonal">
        <VIcon icon="tabler-truck" size="40" />
      </VAvatar>

      <div>
        <h2 class="text-h4 font-weight-bold mb-0">
          {{ settings?.site_identity?.site_name || 'شركة الشحن' }}
        </h2>
        <p class="text-caption mb-0 text-secondary">
          {{ settings?.site_identity?.site_address || 'نظام إدارة الشحن اللوجستي' }}
        </p>
        <p v-if="settings?.site_identity?.site_phone" class="text-caption mb-0">
          {{ settings.site_identity.site_phone }}
        </p>
      </div>
    </div>

    <div class="text-left">
      <h3 class="text-h3 font-weight-black mb-1 text-primary">{{ title }}</h3>
      <p v-if="subtitle" class="text-subtitle-2 mb-2 text-secondary">{{ subtitle }}</p>
      <div v-for="(line, index) in metaLines" :key="index" class="text-subtitle-2">
        {{ line.label }}: <span class="font-weight-bold">{{ line.value }}</span>
      </div>
    </div>
  </div>
</template>

