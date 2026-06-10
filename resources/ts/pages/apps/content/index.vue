<script setup lang="ts">
import ContentList from '@/views/apps/content/ContentList.vue';

definePage({
  meta: {
    action: 'manage',
    subject: 'content.page',
  },
})
</script>

<template>
  <ContentList />
</template>
