<script setup lang="ts">
import { useApi } from '@/composables/useApi'
import { createUrl } from '@core/composable/createUrl'
import { avatarText } from '@core/utils/formatters'

const searchQuery = ref('')
const itemsPerPage = ref(25)
const page = ref(1)

//    Fetching activity logs
const { data: logsData, execute: fetchLogs } = await useApi<any>(createUrl('/activity-logs', {
  query: {
    q: searchQuery,
    per_page: itemsPerPage,
    page,
  },
}))

const logs = computed(() => logsData.value?.data || [])
const totalLogs = computed(() => logsData.value?.total || 0)

const headers = [
  { title: 'User', key: 'user' },
  { title: 'Activity', key: 'activity' },
  { title: 'Type', key: 'type' },
  { title: 'Source', key: 'source' },
  { title: 'Date', key: 'created_at' },
  { title: 'Action', key: 'actions', sortable: false },
]

const resolveTypeColor = (type: string) => {
  if (type === 'CREATE') return 'success'
  if (type === 'UPDATE') return 'info'
  if (type === 'DELETE') return 'error'
  if (type === 'LOGIN') return 'primary'
  return 'secondary'
}

const isViewDrawerVisible = ref(false)
const selectedLog = ref<any>(null)

const viewLog = (log: any) => {
  selectedLog.value = log
  isViewDrawerVisible.value = true
}
</script>

<template>
  <section>
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <div class="d-flex align-center flex-wrap gap-4">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search Activity..."
            style="inline-size: 20rem;"
          />
        </div>
      </VCardText>

      <VDivider />

      <VDataTableServer
        v-model:page="page"
        :items="logs"
        :items-length="totalLogs"
        :headers="headers"
        :items-per-page="itemsPerPage"
        hide-default-footer
        class="text-no-wrap"
      >
        <!-- User -->
        <template #item.user="{ item }">
          <div class="d-flex align-center gap-x-3">
            <VAvatar size="34" color="secondary" variant="tonal">
              <span class="text-xs">{{ avatarText(item.user?.name || 'System') }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base mb-0">
                {{ item.user?.name || 'System' }}
              </h6>
              <span class="text-sm text-disabled">@{{ item.user?.username || 'system' }}</span>
            </div>
          </div>
        </template>

        <!-- Activity -->
        <template #item.activity="{ item }">
          <div class="d-flex flex-column" style="max-width: 300px;">
            <span class="text-base text-high-emphasis text-truncate">{{ item.activity }}</span>
            <span class="text-sm text-disabled text-truncate">{{ item.description }}</span>
          </div>
        </template>

        <!-- Type -->
        <template #item.type="{ item }">
          <VChip
            size="small"
            :color="resolveTypeColor(item.type)"
            variant="tonal"
            class="text-capitalize"
          >
            {{ item.type }}
          </VChip>
        </template>

        <!-- Source -->
        <template #item.source="{ item }">
          <div v-if="item.login_session" class="d-flex flex-column">
            <span class="text-sm">{{ item.login_session.ip_address }}</span>
            <span class="text-xs text-disabled">{{ item.login_session.city }}, {{ item.login_session.country }}</span>
          </div>
          <span v-else>-</span>
        </template>

        <!-- Date -->
        <template #item.created_at="{ item }">
          <span class="text-sm">{{ new Date(item.created_at).toLocaleString() }}</span>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <IconBtn size="small" @click="viewLog(item)">
            <VIcon icon="tabler-eye" />
            <VTooltip activator="parent">View Full Log</VTooltip>
          </IconBtn>
        </template>

        <template #bottom>
          <TablePagination
            v-model:page="page"
            v-model:itemsPerPage="itemsPerPage"
            :total-items="totalLogs"
          />
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Detailed View Dialog (Minimal) -->
    <VDialog v-model="isViewDrawerVisible" max-width="800">
      <VCard title="Activity Detail">
        <VCardText v-if="selectedLog">
          <VRow>
            <VCol cols="12" md="6">
              <h6 class="text-h6 mb-2">Basic Info</h6>
              <p><strong>Activity:</strong> {{ selectedLog.activity }}</p>
              <p><strong>Type:</strong> {{ selectedLog.type }}</p>
              <p><strong>Time:</strong> {{ new Date(selectedLog.created_at).toLocaleString() }}</p>
            </VCol>
            <VCol cols="12" md="6">
              <h6 class="text-h6 mb-2">Source Information</h6>
              <div v-if="selectedLog.login_session">
                 <p><strong>IP:</strong> {{ selectedLog.login_session.ip_address }}</p>
                 <p><strong>City/Country:</strong> {{ selectedLog.login_session.city }}, {{ selectedLog.login_session.country }}</p>
                 <p><strong>Device:</strong> {{ selectedLog.login_session.device_name || 'N/A' }}</p>
              </div>
              <p v-else>Source information not available.</p>
            </VCol>
            <VCol cols="12">
               <h6 class="text-h6 mb-2">Description</h6>
               <p>{{ selectedLog.description }}</p>
            </VCol>

            <!-- Values Change if Any -->
             <VCol cols="12" v-if="selectedLog.new_values && Object.keys(selectedLog.new_values).length">
               <h6 class="text-h6 mb-2">Changes</h6>
               <VTable density="compact" class="border rounded">
                 <thead>
                   <tr>
                     <th>Property</th>
                     <th>Old Value</th>
                     <th>New Value</th>
                   </tr>
                 </thead>
                 <tbody>
                   <tr v-for="(val, key) in selectedLog.new_values" :key="key">
                     <td class="font-weight-bold">{{ key }}</td>
                     <td class="text-error">{{ selectedLog.old_values?.[key] ?? '(empty)' }}</td>
                     <td class="text-success">{{ val }}</td>
                   </tr>
                 </tbody>
               </VTable>
             </VCol>
          </VRow>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn color="secondary" @click="isViewDrawerVisible = false">Close</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>
