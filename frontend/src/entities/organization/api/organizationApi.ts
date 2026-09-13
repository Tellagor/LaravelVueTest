import { httpClient, ensureCsrfCookie } from '@/shared/api/httpClient'
import type { Organization } from '@/entities/organization/model/types'

export const organizationApi = {
  async fetchCurrent(): Promise<Organization | null> {
    const { data } = await httpClient.get<{ organization: Organization | null }>('/organization')
    return data.organization
  },

  async save(yandexUrl: string): Promise<Organization> {
    await ensureCsrfCookie()
    const { data } = await httpClient.post<{ organization: Organization }>('/organization', {
      yandex_url: yandexUrl,
    })
    return data.organization
  },
}
