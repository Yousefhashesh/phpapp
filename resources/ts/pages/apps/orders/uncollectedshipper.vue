<script setup lang="ts">
import OrdersUncollectedShipper from '@/views/apps/orders/special-views/OrdersUncollectedShipper.vue';
</script>

<template>
  <div>
    <VRow>
      <VCol cols="12">
        <OrdersUncollectedShipper />
      </VCol>
    </VRow>
  </div>
</template>

<route lang="yaml">
meta:
  action: manage
  subject: order.uncollectedshipper.page
</route>

