<script setup lang="ts">
import ShipperReturnList from '@/views/apps/orders/returns/ShipperReturnList.vue';
</script>

<template>
  <VRow>
    <VCol cols="12">
      <h4 class="text-h4 mb-4">Shipper Returns</h4>
      <ShipperReturnList />
    </VCol>
  </VRow>
</template>

<route lang="yaml">
meta:
  action: manage
  subject: shipper-return.page
</route>

