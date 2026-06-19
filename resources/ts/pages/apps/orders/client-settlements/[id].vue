<script setup lang="ts">
import FinancialDocumentDetailView from '@/views/apps/orders/settlements/FinancialDocumentDetailView.vue'

definePage({
  meta: {
    action: 'manage',
    subject: 'client-settlement.page',
  },
})
</script>

<template>
  <FinancialDocumentDetailView document-type="client-settlement" />
</template>
