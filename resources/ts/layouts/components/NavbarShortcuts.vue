<script setup lang="ts">
const shortcuts = [
  {
    icon: 'tabler-package',
    title: 'Orders',
    subtitle: 'Manage Orders',
    to: { name: 'apps-orders' },
  },
  {
    icon: 'tabler-users-group',
    title: 'Staff',
    subtitle: 'Staff Users',
    to: { name: 'apps-user-list' },
  },
  {
    icon: 'tabler-users',
    title: 'Clients',
    subtitle: 'Manage Clients',
    to: { name: 'apps-user-clients' },
  },
  {
    icon: 'tabler-truck',
    title: 'Shippers',
    subtitle: 'Manage Shippers',
    to: { name: 'apps-user-shippers' },
  },
  {
    icon: 'tabler-currency-dollar',
    title: 'Settlements',
    subtitle: 'Client Settlements',
    to: { name: 'apps-orders-client-settlements' },
  },
  {
    icon: 'tabler-settings',
    title: 'Settings',
    subtitle: 'System Settings',
    to: { name: 'apps-settings' },
  },
]
</script>

<template>
  <Shortcuts :shortcuts="shortcuts" />
</template>
