export default [
  {
    title: 'Pages',
    icon: { icon: 'tabler-file' },
    children: [

      {
        title: 'User Profile',
        icon: { icon: 'tabler-user-circle' },
        to: { name: 'pages-user-profile-tab', params: { tab: 'profile' } },
      },
      {
        title: 'Account Settings',
        icon: { icon: 'tabler-settings' },
        to: { name: 'pages-account-settings-tab', params: { tab: 'account' } },
      },
      { title: 'FAQ', icon: { icon: 'tabler-help' }, to: 'pages-faq' },
      { title: 'Pricing', icon: { icon: 'tabler-diamond' }, to: 'pages-pricing' },
      {
        title: 'Misc',
        icon: { icon: 'tabler-cube' },
        children: [
          { title: 'Coming Soon', to: 'pages-misc-coming-soon' },
          { title: 'Under Maintenance', to: 'pages-misc-under-maintenance', target: '_blank' },
          { title: 'Page Not Found - 404', to: { path: '/pages/misc/not-found' }, target: '_blank' },
          { title: 'Not Authorized - 401', to: { path: '/pages/misc/not-authorized' }, target: '_blank' },
        ],
      },
      {
        title: 'Authentication',
        icon: { icon: 'tabler-lock' },
        children: [
          {
            title: 'Login',
            to: 'pages-authentication-login-v1',
            target: '_blank',
          },
        ],
      },
      {
        title: 'Wizard Pages',
        icon: { icon: 'tabler-forms' },
        children: [
          { title: 'Checkout', to: { name: 'wizard-examples-checkout' } },
          { title: 'Property Listing', to: { name: 'wizard-examples-property-listing' } },
          { title: 'Create Deal', to: { name: 'wizard-examples-create-deal' } },
        ],
      },
      { title: 'Dialog Examples', icon: { icon: 'tabler-square' }, to: 'pages-dialog-examples' },
      {
        title: 'Front Pages',
        icon: { icon: 'tabler-files' },
        children: [
          {
            title: 'Landing',
            to: 'front-pages-landing-page',
            target: '_blank',

          },
          {
            title: 'Pricing',
            to: 'front-pages-pricing',
            target: '_blank',

          },
          {
            title: 'Payment',
            to: 'front-pages-payment',
            target: '_blank',

          },
          {
            title: 'Checkout',
            to: 'front-pages-checkout',
            target: '_blank',

          },
          {
            title: 'Help Center',
            to: 'front-pages-help-center',
            target: '_blank',

          },
        ],
      },
    ],
  },
]
