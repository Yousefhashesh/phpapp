<script setup lang="ts">
import UserBioPanel from '@/views/apps/user/view/UserBioPanel.vue'
import UserTabAccount from '@/views/apps/user/view/UserTabAccount.vue'
import UserTabSecurity from '@/views/apps/user/view/UserTabSecurity.vue'
import UserTabTimeline from '@/views/apps/user/view/UserTabTimeline.vue'

definePage({
  meta: {
    action: 'manage',
    subject: 'user.page',
  },
})


const route = useRoute('apps-user-view-id')

const userTab = ref(0)

const tabs = [
  { icon: 'tabler-users', title: 'Account' },
  { icon: 'tabler-history', title: 'Timeline' },
  { icon: 'tabler-lock', title: 'Security' },
]

// Re-fetching function
const { data: userData, execute: fetchUserData } = await useApi<any>(`/users/${route.params.id}`)
</script>

<template>
  <VRow v-if="userData">
    <VCol
      cols="12"
      md="5"
      lg="4"
    >
      <UserBioPanel
        :user-data="userData"
        @update="fetchUserData"
      />
    </VCol>

    <VCol
      cols="12"
      md="7"
      lg="8"
    >
      <VTabs
        v-model="userTab"
        class="v-tabs-pill"
      >
        <VTab
          v-for="tab in tabs"
          :key="tab.icon"
        >
          <VIcon
            :size="18"
            :icon="tab.icon"
            class="me-1"
          />
          <span>{{ tab.title }}</span>
        </VTab>
      </VTabs>

      <VWindow
        v-model="userTab"
        class="mt-6 disable-tab-transition"
        :touch="false"
      >
        <VWindowItem>
          <UserTabAccount :user-data="userData" @more="userTab = 1" />
        </VWindowItem>

        <VWindowItem>
          <UserTabTimeline :user-data="userData" />
        </VWindowItem>

        <VWindowItem>
          <UserTabSecurity :user-data="userData" />
        </VWindowItem>
      </VWindow>
    </VCol>
  </VRow>
  <div v-else>
    <VAlert
      type="error"
      variant="tonal"
    >
      Invoice with ID  {{ route.params.id }} not found!
    </VAlert>
  </div>
</template>
