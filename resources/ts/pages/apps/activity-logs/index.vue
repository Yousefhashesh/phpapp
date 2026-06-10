<script setup lang="ts">
import ActivityLogList from '@/views/apps/activity-log/ActivityLogList.vue';

definePage({
  meta: {
    action: 'manage',
    subject: 'activity-log.page',
  },
})
</script>

<template>
  <div>
    <h4 class="text-h4 mb-4">Activity Logs</h4>
    <ActivityLogList />
  </div>
</template>
