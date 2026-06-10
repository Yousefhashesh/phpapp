<script setup lang="ts">
import OrdersUnreturnClient from '@/views/apps/orders/special-views/OrdersUnreturnClient.vue';
</script>

<template>
  <div>
    <VRow>
      <VCol cols="12">
        <OrdersUnreturnClient />
      </VCol>
    </VRow>
  </div>
</template>

<route lang="yaml">
meta:
  action: manage
  subject: order.unreturnclient.page
</route>

