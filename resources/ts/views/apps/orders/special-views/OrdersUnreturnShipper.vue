<template>
  <OrderList :fixed-filters="{ shipper_return_pending: 1 }" title="أوردرات Unreturn Shipper" />
</template>

<script setup lang="ts">
import OrderList from '../OrderList.vue';
</script>

