<script setup lang="ts">
import OrderList from '@/views/apps/orders/OrderList.vue';
</script>

<template>
  <VRow>
    <VCol cols="12">
      <h4 class="text-h4 mb-4">Orders Management</h4>
      <OrderList />
    </VCol>
  </VRow>
</template>

<route lang="yaml">
meta:
  action: manage
  subject: order.page
</route>
