<script setup lang="ts">
import ApprovalRequestList from '@/views/apps/orders/approvals/ApprovalRequestList.vue';
</script>

<template>
  <div>
    <VRow>
      <VCol cols="12">
        <ApprovalRequestList />
      </VCol>
    </VRow>
  </div>
</template>

<route lang="yaml">
meta:
  action: manage
  subject: order.approval.page
</route>

