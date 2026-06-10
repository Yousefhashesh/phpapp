<script setup lang="ts">
import authV1BottomShape from '@images/svg/auth-v1-bottom-shape.svg?raw'
import authV1TopShape from '@images/svg/auth-v1-top-shape.svg?raw'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { VForm } from 'vuetify/components/VForm'

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const route = useRoute()
const router = useRouter()
const ability = useAbility()

const errors = ref<Record<string, string | undefined>>({
  login: undefined,
  password: undefined,
})

const refVForm = ref<VForm>()

const form = ref({
  login: '', // You can use "johndoe" or "01000000000" to login with fake API
  password: '',
  remember: false,
})

const isPasswordVisible = ref(false)

interface LoginApiResponse {
  message: string
  token_type: string
  access_token: string
  user: Record<string, any>
}

interface AclMatrixResponse {
  pages?: Record<string, boolean>
  actions?: Record<string, boolean>
}

const buildAbilityRulesFromAcl = (acl: any) => {
  const grantedPermissions = acl.ability_rules || [
    ...Object.entries(acl.pages ?? {}),
    ...Object.entries(acl.actions ?? {}),
  ]
    .filter(([, allowed]) => !!allowed)
    .map(([permission]) => permission)

  if (!grantedPermissions.length)
    return []

  // Generate both formats for compatibility:
  // { action: 'manage', subject: permission } -> Used by Sidebar
  // { action: permission, subject: 'all' } -> Used by many page components
  const rules: any[] = []
  grantedPermissions.forEach((permission: string) => {
    if (permission === '*') {
      rules.push({ action: 'manage', subject: 'all' })
    }
    else {
      rules.push({ action: 'manage', subject: permission })
      rules.push({ action: permission, subject: 'all' })
    }
  })
  
  return rules
}

const login = async () => {
  errors.value = {
    login: undefined,
    password: undefined,
  }

  try {
    const res = await $api<LoginApiResponse>('/login', {
      method: 'POST',
      body: {
        login: form.value.login,
        password: form.value.password,
        device_name: 'web-vue',
      },
      onResponseError({ response }: { response: any }) {
        const message = response?._data?.message as string | undefined
        const validation = response?._data?.errors as Record<string, string[]> | undefined

        errors.value = {
          login: validation?.login?.[0] ?? validation?.email?.[0] ?? message,
          password: validation?.password?.[0],
        }
      },
    })

    useCookie('accessToken').value = res.access_token
    useCookie<any>('userData').value = res.user

    let userAbilityRules: Array<{ action: string; subject: string }> = []

    try {
      const acl = await $api<AclMatrixResponse>('/acl', {
        headers: {
          Authorization: `Bearer ${res.access_token}`,
        },
      })
      userAbilityRules = buildAbilityRulesFromAcl(acl)
    }
    catch (e) {
      console.error('Failed to fetch ACL', e)
      userAbilityRules = [] // No permissions on failure
    }

    useLocalStorage('userAbilityRules', []).value = userAbilityRules as any
    ability.update(userAbilityRules as any)

    await nextTick(async () => {
      let targetRoute: any = route.query.to ? String(route.query.to) : { name: 'dashboards-orders' }

      // If heading to default dashboard but it's forbidden, let's find the first authorized page
      if ((targetRoute === '/dashboards/orders' || targetRoute.name === 'dashboards-orders') && !ability.can('manage', 'order.dashboard.page' as any)) {
        const { default: navItems } = await import('@/navigation/vertical')
        let firstAuthorizedTarget: string | null = null

        for (const item of navItems) {
          if ('children' in item && item.children) {
            for (const child of item.children) {
              if ('to' in child && child.to && 'action' in child && 'subject' in child && ability.can(child.action as string, child.subject as string)) {
                firstAuthorizedTarget = typeof child.to === 'string' ? child.to : (child.to as any).name
                break
              }
            }
          } else if ('to' in item && item.to && 'action' in item && 'subject' in item && ability.can(item.action as string, item.subject as string)) {
            firstAuthorizedTarget = typeof item.to === 'string' ? item.to : (item.to as any).name
            break
          }
          if (firstAuthorizedTarget) break
        }

        if (firstAuthorizedTarget) {
          targetRoute = { name: firstAuthorizedTarget }
        } else {
          // Fallback if the user has absolutely NO accessible menu items
          targetRoute = { name: 'not-authorized' }
        }
      }

      router.replace(targetRoute)
    })
  }
  catch (err) {
    console.error(err)
  }
}

const onSubmit = () => {
  refVForm.value?.validate()
    .then(({ valid: isValid }) => {
      if (isValid)
        login()
    })
}
</script>

<template>
  <div class="auth-wrapper d-flex align-center justify-center pa-4">
    <div class="position-relative my-sm-16">
      <!-- 👉 Top shape -->
      <VNodeRenderer
        :nodes="h('div', { innerHTML: authV1TopShape })"
        class="text-primary auth-v1-top-shape d-none d-sm-block"
      />

      <!-- 👉 Bottom shape -->
      <VNodeRenderer
        :nodes="h('div', { innerHTML: authV1BottomShape })"
        class="text-primary auth-v1-bottom-shape d-none d-sm-block"
      />

      <!-- 👉 Auth Card -->
      <VCard
        class="auth-card"
        max-width="460"
        :class="$vuetify.display.smAndUp ? 'pa-6' : 'pa-0'"
      >
        <VCardItem class="justify-center">
          <VCardTitle>
            <RouterLink to="/">
              <div class="app-logo">
                <VNodeRenderer :nodes="themeConfig.app.logo" />
                <h1 class="app-logo-title">
                  {{ themeConfig.app.title }}
                </h1>
              </div>
            </RouterLink>
          </VCardTitle>
        </VCardItem>

        <VCardText>
          <h4 class="text-h4 mb-1">
            Welcome to <span class="text-capitalize">{{ themeConfig.app.title }}</span>! 👋🏻
          </h4>
          <p class="mb-0">
            Please sign-in to your account and start the adventure
          </p>
        </VCardText>

        <VCardText>
          <VForm
            ref="refVForm"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- login -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.login"
                  autofocus
                  label="Username or Phone"
                  type="text"
                  placeholder="username or 01000000000"
                  :rules="[requiredValidator]"
                  :error-messages="errors.login"
                />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.password"
                  label="Password"
                  placeholder="············"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="password"
                  :rules="[requiredValidator]"
                  :error-messages="errors.password"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />

                <!-- remember me checkbox -->
                <div class="d-flex align-center justify-space-between flex-wrap my-6">
                  <VCheckbox
                    v-model="form.remember"
                    label="Remember me"
                  />

                  <span class="text-disabled">Secure access only</span>
                </div>

                <!-- login button -->
                <VBtn
                  block
                  type="submit"
                >
                  Login
                </VBtn>
              </VCol>

            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </div>
  </div>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth";
</style>
