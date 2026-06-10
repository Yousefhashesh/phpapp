/**
 * First in-app route the current user is allowed to open (orders, dashboard, collections, etc.).
 */
export function useAppEntryRoute() {
  const ability = useAbility()

  const entryCandidates = [
    { name: 'apps-orders', subject: 'order.page' },
    { name: 'dashboards-orders', subject: 'order.dashboard.page' },
    { name: 'apps-orders-shipper-collections', subject: 'shipper-collection.page' },
    { name: 'apps-orders-shipper-returns', subject: 'shipper-return.page' },
    { name: 'apps-orders-client-settlements', subject: 'client-settlement.page' },
    { name: 'apps-orders-client-returns', subject: 'client-return.page' },
    { name: 'apps-operations-pickups', subject: 'pickup-request.page' },
    { name: 'apps-operations-material-requests', subject: 'material-request.page' },
    { name: 'apps-settings', subject: 'setting.page' },
  ] as const

  const appEntryRoute = computed(() => {
    for (const candidate of entryCandidates) {
      if (ability.can('manage', candidate.subject as any))
        return { name: candidate.name }
    }

    return { name: 'not-authorized' as const }
  })

  const hasAppAccess = computed(() => appEntryRoute.value.name !== 'not-authorized')

  const router = useRouter()

  const goToAppEntry = async () => {
    await router.push(appEntryRoute.value)
  }

  return {
    appEntryRoute,
    hasAppAccess,
    goToAppEntry,
  }
}
