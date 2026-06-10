<script setup lang="ts">
import RefusedReasonList from '@/views/apps/refused-reason/RefusedReasonList.vue';

definePage({
  meta: {
    action: 'manage',
    subject: 'refused-reason.page',
  },
})
</script>

<template>
  <RefusedReasonList />
</template>
