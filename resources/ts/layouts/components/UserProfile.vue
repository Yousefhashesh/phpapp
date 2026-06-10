<script setup lang="ts">
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const router = useRouter()
const ability = useAbility()

// TODO: Get type from backend
const userData = useCookie<any>('userData')

const logout = async () => {
  try {
    await $api('/logout', { method: 'POST' })
  }
  catch {
    // proceed with client-side cleanup even if the request fails
  }

  useCookie('accessToken').value = null
  userData.value = null

  useLocalStorage('userAbilityRules', []).value = null
  ability.update([])

  await router.push({ name: 'pages-authentication-login-v1' })
}

const userProfileList = computed(() => [
  { type: 'divider' },
  { 
    type: 'navItem', 
    icon: userData.value?.avatar ? null : 'tabler-user', 
    avatar: userData.value?.avatar,
    title: 'Profile', 
    to: { name: 'apps-user-view-id', params: { id: userData.value?.id || userData.value?.user_id } } 
  },
  { type: 'navItem', icon: 'tabler-settings', title: 'Settings', to: { name: 'pages-account-settings-tab', params: { tab: 'account' } } },
  { type: 'divider' },
])
</script>

<template>
  <VBadge
    v-if="userData"
    dot
    bordered
    location="bottom right"
    offset-x="1"
    offset-y="2"
    color="success"
  >
    <VAvatar
      size="38"
      class="cursor-pointer"
      :color="!(userData && userData.avatar) ? 'primary' : undefined"
      :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
    >
      <VImg
        v-if="userData && userData.avatar"
        :src="userData.avatar"
      />
      <VIcon
        v-else
        icon="tabler-user"
      />

      <!-- SECTION Menu -->
      <VMenu
        activator="parent"
        width="240"
        location="bottom end"
        offset="12px"
      >
        <VList>
          <VListItem>
            <div class="d-flex gap-2 align-center">
              <VListItemAction>
                <VBadge
                  dot
                  location="bottom right"
                  offset-x="3"
                  offset-y="3"
                  color="success"
                  bordered
                >
                  <VAvatar
                    :color="!(userData && userData.avatar) ? 'primary' : undefined"
                    :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
                  >
                    <VImg
                      v-if="userData && userData.avatar"
                      :src="userData.avatar"
                    />
                    <VIcon
                      v-else
                      icon="tabler-user"
                    />
                  </VAvatar>
                </VBadge>
              </VListItemAction>

              <div>
                <h6 class="text-h6 font-weight-medium">
                  {{ userData.fullName || userData.username }}
                </h6>
                <VListItemSubtitle class="text-capitalize text-disabled">
                  {{ userData.role }}
                </VListItemSubtitle>
              </div>
            </div>
          </VListItem>

          <PerfectScrollbar :options="{ wheelPropagation: false }">
            <template
              v-for="item in (userProfileList as any)"
              :key="item.title"
            >
              <VListItem
                v-if="item.type === 'navItem'"
                :to="item.to"
              >
                <template #prepend>
                  <VAvatar
                    v-if="item.avatar"
                    size="22"
                    class="me-2"
                  >
                    <VImg :src="item.avatar" />
                  </VAvatar>
                  <VIcon
                    v-else-if="item.icon"
                    :icon="item.icon"
                    size="22"
                  />
                </template>

                <VListItemTitle>{{ item.title }}</VListItemTitle>

              </VListItem>

              <VDivider
                v-else
                class="my-2"
              />
            </template>

            <div class="px-4 py-2">
              <VBtn
                block
                size="small"
                color="error"
                append-icon="tabler-logout"
                @click="logout"
              >
                Logout
              </VBtn>
            </div>
          </PerfectScrollbar>
        </VList>
      </VMenu>
      <!-- !SECTION -->
    </VAvatar>
  </VBadge>
</template>
