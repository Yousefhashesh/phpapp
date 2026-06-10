<script setup lang="ts">
import OrderList from '@/views/apps/orders/OrderList.vue';
</script>

<template>
  <OrderList 
    title="Rejected Orders" 
    :fixed-filters="{ approval_status: 'REJECTED' }"
  />
</template>

<route lang="yaml">
meta:
  action: manage
  subject: order.page
</route>
