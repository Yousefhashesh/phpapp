import { useLocalStorage } from '@vueuse/core'
import type { App } from 'vue'

import { createMongoAbility } from '@casl/ability'
import { abilitiesPlugin } from '@casl/vue'
import type { Rule } from './ability'

export default function (app: App) {
  const userAbilityRules = useLocalStorage<Rule[]>('userAbilityRules', [])
  const initialAbility = createMongoAbility(userAbilityRules.value ?? [])

  app.use(abilitiesPlugin, initialAbility, {
    useGlobalProperties: true,
  })
}
