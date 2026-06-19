<script setup lang="ts">
import FinancialDocumentDetailView from '@/views/apps/orders/settlements/FinancialDocumentDetailView.vue'

definePage({
  meta: {
    action: 'manage',
    subject: 'shipper-collection.page',
  },
})
</script>

<template>
  <FinancialDocumentDetailView document-type="shipper-collection" />
</template>
