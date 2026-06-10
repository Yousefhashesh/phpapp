<template>
  <OrderList :fixed-filters="{ status: 'UNDELIVERED', is_shipper_returned: 1, is_client_returned: 0 }" title="أوردرات Unreturn Client" />
</template>

<script setup lang="ts">
import OrderList from '../OrderList.vue';
</script>
