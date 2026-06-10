<template>
  <OrderList :fixed-filters="{ status: ['HOLD', 'OUT_FOR_DELIVERY'], approval_status: ['APPROVED', 'PENDING'] }" title=" HOLD و OUT_FOR_DELIVERY" />
</template>

<script setup lang="ts">
import OrderList from '../OrderList.vue';
</script>

