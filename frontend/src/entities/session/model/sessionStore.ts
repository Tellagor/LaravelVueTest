import { defineStore } from 'pinia'
import { sessionApi } from '@/entities/session/api/sessionApi'
import type { User } from '@/entities/session/model/types'

interface SessionState {
  user: User | null
  initialized: boolean
}

export const useSessionStore = defineStore('session', {
  state: (): SessionState => ({
    user: null,
    initialized: false,
  }),

  getters: {
    isAuthenticated: (state): boolean => state.user !== null,
  },

  actions: {
    setUser(user: User | null): void {
      this.user = user
    },

    async fetchCurrentUser(): Promise<void> {
      try {
        this.user = await sessionApi.fetchCurrentUser()
      } catch {
        this.user = null
      } finally {
        this.initialized = true
      }
    },
  },
})
