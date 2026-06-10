<template>
  <OrderList :fixed-filters="{ status: ['DELIVERED', 'UNDELIVERED'], is_client_settled: 0, is_shipper_collected: 1, is_in_client_settlement: 0 }" title="أوردرات Uncollected Client" />
</template>

<script setup lang="ts">
import OrderList from '../OrderList.vue';
</script>

