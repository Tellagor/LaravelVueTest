import { httpClient, ensureCsrfCookie } from '@/shared/api/httpClient'
import type { User } from '@/entities/session/model/types'

export const sessionApi = {
  async login(email: string, password: string): Promise<User> {
    await ensureCsrfCookie()
    const { data } = await httpClient.post<{ user: User }>('/login', { email, password })
    return data.user
  },

  async logout(): Promise<void> {
    await httpClient.post('/logout')
  },

  async fetchCurrentUser(): Promise<User | null> {
    const { data } = await httpClient.get<{ user: User | null }>('/user')
    return data.user
  },
}
