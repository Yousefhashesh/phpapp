<script setup lang="ts">
interface Props {
  userData: {
    id: number
  }
}

const props = defineProps<Props>()

const logs = ref<any[]>([])
const isFetching = ref(false)

const fetchLogs = async () => {
  isFetching.value = true
  try {
    const res = await $api('/activity-logs', {
      params: { 
        user_id: props.userData.id, 
        itemsPerPage: 20,
        page: 1
      }
    })
    logs.value = res.data || []
  } catch (e) {
    // 
  }
  isFetching.value = false
}

const getIcon = (type: string) => {
  const t = type?.toUpperCase()
  if (t === 'CREATE') return 'tabler-circle-plus'
  if (t === 'UPDATE') return 'tabler-edit'
  if (t === 'DELETE') return 'tabler-trash'
  return 'tabler-circle-check'
}

const getColor = (type: string) => {
  const t = type?.toUpperCase()
  if (t === 'CREATE') return 'success'
  if (t === 'UPDATE') return 'info'
  if (t === 'DELETE') return 'error'
  return 'primary'
}

onMounted(() => {
  fetchLogs()
})
</script>

<template>
  <VCard title="User Activity Timeline">
    <VCardText>
      <VTimeline
        side="end"
        align="start"
        truncate-line="both"
        density="compact"
        class="v-timeline-activity"
      >
        <VTimelineItem
          v-for="log in logs"
          :key="log.id"
          :dot-color="getColor(log.type)"
          size="x-small"
        >
          <template #icon>
            <VIcon :icon="getIcon(log.type)" size="12" color="white" />
          </template>

          <div class="d-flex justify-space-between align-center gap-2 mb-1">
            <span class="text-h6 font-weight-medium text-capitalize">
              {{ log.activity }}
            </span>
            <span class="text-xs text-disabled">
              {{ new Date(log.created_at).toLocaleString() }}
            </span>
          </div>
          
          <p v-if="log.description" class="text-body-2 mb-0">
            {{ log.description }}
          </p>
        </VTimelineItem>

        <VTimelineItem v-if="logs.length === 0 && !isFetching" dot-color="primary" size="x-small">
          <p class="text-body-2 mb-0 text-disabled">No activity recorded yet.</p>
        </VTimelineItem>
        
        <VTimelineItem v-if="isFetching" dot-color="secondary" size="x-small">
          <p class="text-body-2 mb-0 italic">Fetching activity history...</p>
        </VTimelineItem>
      </VTimeline>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.v-timeline-activity {
  padding-inline-start: 1rem;
}
</style>
