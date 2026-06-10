<script setup lang="ts">
import PlanList from '@/views/apps/plan/PlanList.vue';

definePage({
  meta: {
    action: 'manage',
    subject: 'plan.page',
  },
})
</script>

<template>
  <PlanList />
</template>
