<script setup lang="ts">
import OrderList from '@/views/apps/orders/OrderList.vue';
</script>

<template>
  <OrderList 
    title="Deleted Orders (Trash)" 
    trashed="only"
  />
</template>

<route lang="yaml">
meta:
  action: manage
  subject: order.page
</route>
