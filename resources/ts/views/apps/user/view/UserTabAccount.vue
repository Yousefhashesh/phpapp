<script lang="ts" setup>
import UserInvoiceTable from './UserInvoiceTable.vue'

interface Props {
  userData: {
    id: number
    account_type: number
    login_sessions: any[]
  }
}

const props = defineProps<Props>()
const emit = defineEmits(['more'])

const formatDate = (date: string) => {
  return new Date(date).toLocaleString()
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <!-- 👉 User Activity timeline -->
      <VCard title="Recent Activity & Sessions">
        <VCardText>
          <VTimeline
            side="end"
            align="start"
            line-inset="8"
            truncate-line="start"
            density="compact"
          >
            <VTimelineItem
              v-for="session in props.userData.login_sessions?.slice(0, 3)"
              :key="session.id"
              :dot-color="session.is_active ? 'success' : 'secondary'"
              size="x-small"
            >
              <div class="d-flex justify-space-between align-center gap-2 flex-wrap mb-2">
                <span class="app-timeline-title">
                  Login from {{ session.browser || 'Unknown' }} ({{ session.platform || 'Unknown' }})
                </span>
                <span class="app-timeline-meta">{{ formatDate(session.login_at) }}</span>
              </div>

              <div class="app-timeline-text mt-1">
                IP: {{ session.ip_address }} | Location: {{ session.country }}, {{ session.city }}
              </div>
              
              <VChip
                v-if="session.is_current"
                label
                color="primary"
                size="small"
                class="mt-2"
              >
                Current Session
              </VChip>
            </VTimelineItem>
            
            <VTimelineItem
              v-if="!props.userData.login_sessions?.length"
              dot-color="warning"
              size="x-small"
            >
              <div class="app-timeline-title">No recent login sessions found</div>
            </VTimelineItem>
          </VTimeline>

          <VBtn
            v-if="props.userData.login_sessions?.length > 3"
            variant="tonal"
            size="small"
            class="mt-6"
            @click="emit('more')"
          >
            عرض كل النشاطات والسجلات
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12">
      <UserInvoiceTable :user-data="props.userData" />
    </VCol>
  </VRow>
</template>
