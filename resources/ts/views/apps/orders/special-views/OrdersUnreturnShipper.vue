<template>
  <OrderList :fixed-filters="{ status: 'UNDELIVERED', is_shipper_returned: 0 }" title="أوردرات Unreturn Shipper" />
</template>

<script setup lang="ts">
import OrderList from '../OrderList.vue';
</script>

