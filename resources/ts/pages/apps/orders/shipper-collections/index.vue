<script setup lang="ts">
import ShipperCollectionList from '@/views/apps/orders/settlements/ShipperCollectionList.vue';
</script>

<template>
  <VRow>
    <VCol cols="12">
      <h4 class="text-h4 mb-4">Shipper Collections</h4>
      <ShipperCollectionList />
    </VCol>
  </VRow>
</template>

<route lang="yaml">
meta:
  action: manage
  subject: shipper-collection.page
</route>

