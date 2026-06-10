<script setup lang="ts">
import OrdersUnreturnShipper from '@/views/apps/orders/special-views/OrdersUnreturnShipper.vue';
</script>

<template>
  <div>
    <VRow>
      <VCol cols="12">
        <OrdersUnreturnShipper />
      </VCol>
    </VRow>
  </div>
</template>

<route lang="yaml">
meta:
  action: manage
  subject: order.unreturnshipper.page
</route>

