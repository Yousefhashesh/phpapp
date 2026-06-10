<script setup lang="ts">
import ClientSettlementList from '@/views/apps/orders/settlements/ClientSettlementList.vue';
</script>

<template>
  <VRow>
    <VCol cols="12">
      <h4 class="text-h4 mb-4">Client Settlements</h4>
      <ClientSettlementList />
    </VCol>
  </VRow>
</template>

<route lang="yaml">
meta:
  action: manage
  subject: client-settlement.page
</route>

