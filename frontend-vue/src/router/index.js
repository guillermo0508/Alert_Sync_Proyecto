import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/RegisterView.vue'),
      meta: { guest: true },
    },
    {
      path: '/terms',
      name: 'terms',
      component: () => import('@/views/TermsView.vue'),
    },
    {
      path: '/privacy',
      name: 'privacy',
      component: () => import('@/views/PrivacyView.vue'),
    },
    {
      path: '/verify-contact/:id',
      name: 'verify-contact',
      component: () => import('@/views/VerifyContactView.vue'),
    },
    {
      path: '/dashboard',
      component: () => import('@/layouts/DashboardLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: () => import('@/views/DashboardView.vue'),
        },
        {
          path: 'contacts',
          name: 'contacts',
          component: () => import('@/views/ContactsView.vue'),
        },
        {
          path: 'alerts',
          name: 'my-alerts',
          component: () => import('@/views/AlertHistoryView.vue'),
        },
        {
          path: 'sos',
          name: 'sos',
          component: () => import('@/views/SosSimulatorView.vue'),
          meta: { demoOnly: true },
        },
        {
          path: 'plans',
          name: 'plans',
          component: () => import('@/views/PlansView.vue'),
        },
        {
          path: 'change-password',
          name: 'change-password',
          component: () => import('@/views/ChangePasswordView.vue'),
        },
        {
          path: 'profile',
          name: 'profile',
          component: () => import('@/views/ProfileView.vue'),
        },
      ],
    },
    {
      path: '/admin',
      component: () => import('@/layouts/AdminLayout.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: () => import('@/views/admin/AdminDashboardView.vue'),
        },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('@/views/admin/AdminUsersView.vue'),
        },
        {
          path: 'payments',
          name: 'admin-payments',
          component: () => import('@/views/admin/AdminPaymentsView.vue'),
        },
        {
          path: 'alerts',
          name: 'admin-alerts',
          component: () => import('@/views/admin/AdminAlertsView.vue'),
        },
        {
          path: 'content',
          name: 'admin-content',
          component: () => import('@/views/admin/AdminSiteContentView.vue'),
        },
        {
          path: 'plans',
          name: 'admin-plans',
          component: () => import('@/views/admin/AdminPlansView.vue'),
        },
      ],
    },
  ],
  scrollBehavior() {
    return { top: 0 }
  },
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  const isAdmin = auth.user?.role === 'admin'

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: isAdmin ? 'admin-dashboard' : 'dashboard' }
  }

  if (isAdmin && to.path.startsWith('/dashboard')) {
    return { name: 'admin-dashboard' }
  }

  if (to.name === 'dashboard' && (auth.user?.plan === 'Demo' || !auth.user?.plan)) {
    return { name: 'sos' }
  }

  if (to.meta.demoOnly && auth.user?.plan && auth.user.plan !== 'Demo') {
    return { name: 'dashboard' }
  }

  if (to.meta.requiresAdmin && auth.user?.role !== 'admin') {
    return { name: 'dashboard' }
  }

  return true
})

export default router
