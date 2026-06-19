<template>
  <OrderList :fixed-filters="{ client_return_pending: 1 }" title="أوردرات Unreturn Client" />
</template>

<script setup lang="ts">
import OrderList from '../OrderList.vue';
</script>
