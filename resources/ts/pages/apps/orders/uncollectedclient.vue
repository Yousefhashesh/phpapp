<script setup lang="ts">
import OrdersUncollectedClient from '@/views/apps/orders/special-views/OrdersUncollectedClient.vue';
</script>

<template>
  <div>
    <VRow>
      <VCol cols="12">
        <OrdersUncollectedClient />
      </VCol>
    </VRow>
  </div>
</template>

<route lang="yaml">
meta:
  action: manage
  subject: order.uncollectedclient.page
</route>

