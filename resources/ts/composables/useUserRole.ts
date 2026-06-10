export function useUserRole() {
  const userData = useCookie<any>('userData')

  const roleNames = computed(() => {
    const names = new Set<string>()

    if (userData.value?.role)
      names.add(String(userData.value.role).toLowerCase())

    for (const role of userData.value?.roles ?? []) {
      const name = typeof role === 'string' ? role : role?.name
      if (name)
        names.add(String(name).toLowerCase())
    }

    return [...names]
  })

  const isClientUser = computed(() => roleNames.value.includes('client'))
  const isShipperUser = computed(() => roleNames.value.includes('shipper'))
  const isAdminUser = computed(() =>
    roleNames.value.some(role => ['admin', 'super-admin', 'super admin'].includes(role)),
  )

  return {
    userData,
    roleNames,
    isClientUser,
    isShipperUser,
    isAdminUser,
  }
}
