<script setup lang="ts">
import ClientList from '@/views/apps/user/list/ClientList.vue';

definePage({
  meta: {
    action: 'manage',
    subject: 'client.page',
  },
})
</script>

<template>
  <ClientList />
</template>
