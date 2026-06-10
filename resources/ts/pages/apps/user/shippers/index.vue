<script setup lang="ts">
import ShipperList from '@/views/apps/user/list/ShipperList.vue';

definePage({
  meta: {
    action: 'manage',
    subject: 'shipper.page',
  },
})
</script>

<template>
  <ShipperList />
</template>
