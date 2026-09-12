import { defineStore } from 'pinia'
import { apiClient, ensureCsrfCookie } from '@/api/client'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    initialized: false,
  }),
  getters: {
    isAuthenticated: (state) => state.user !== null,
  },
  actions: {
    async login(email, password) {
      await ensureCsrfCookie()
      const { data } = await apiClient.post('/login', { email, password })
      this.user = data.user
    },

    async logout() {
      await apiClient.post('/logout')
      this.user = null
    },

    async fetchUser() {
      try {
        const { data } = await apiClient.get('/user')
        this.user = data.user
      } catch {
        this.user = null
      } finally {
        this.initialized = true
      }
    },
  },
})
