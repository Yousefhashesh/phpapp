<script setup lang="ts">
import ClientReturnList from '@/views/apps/orders/returns/ClientReturnList.vue';
</script>

<template>
  <VRow>
    <VCol cols="12">
      <h4 class="text-h4 mb-4">Client Returns</h4>
      <ClientReturnList />
    </VCol>
  </VRow>
</template>

<route lang="yaml">
meta:
  action: manage
  subject: client-return.page
</route>

