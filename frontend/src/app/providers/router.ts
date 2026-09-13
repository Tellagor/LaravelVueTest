import { createRouter, createWebHistory } from 'vue-router'
import { useSessionStore } from '@/entities/session'
import { LoginPage } from '@/pages/login'
import { SettingsPage } from '@/pages/settings'
import { ResultsPage } from '@/pages/results'

export const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginPage,
      meta: { guestOnly: true },
    },
    {
      path: '/settings',
      name: 'settings',
      component: SettingsPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/',
      name: 'results',
      component: ResultsPage,
      meta: { requiresAuth: true },
    },
  ],
})

router.beforeEach(async (to) => {
  const sessionStore = useSessionStore()

  if (!sessionStore.initialized) {
    await sessionStore.fetchCurrentUser()
  }

  if (to.meta.requiresAuth && !sessionStore.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.guestOnly && sessionStore.isAuthenticated) {
    return { name: 'results' }
  }
})
