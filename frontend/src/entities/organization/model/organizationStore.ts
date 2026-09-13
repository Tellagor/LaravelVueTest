import { defineStore } from 'pinia'
import { organizationApi } from '@/entities/organization/api/organizationApi'
import type { Organization } from '@/entities/organization/model/types'

interface OrganizationState {
  organization: Organization | null
  loaded: boolean
}

export const useOrganizationStore = defineStore('organization', {
  state: (): OrganizationState => ({
    organization: null,
    loaded: false,
  }),

  getters: {
    status: (state): Organization['status'] | null => state.organization?.status ?? null,
    isBeingParsed: (state): boolean =>
      state.organization?.status === 'pending' || state.organization?.status === 'processing',
  },

  actions: {
    async fetchCurrent(): Promise<void> {
      this.organization = await organizationApi.fetchCurrent()
      this.loaded = true
    },

    async save(yandexUrl: string): Promise<Organization> {
      this.organization = await organizationApi.save(yandexUrl)
      return this.organization
    },
  },
})
