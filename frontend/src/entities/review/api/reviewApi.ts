import { httpClient } from '@/shared/api/httpClient'
import type { ReviewsPage } from '@/entities/review/model/types'

export const reviewApi = {
  async fetchPage(page = 1): Promise<ReviewsPage> {
    const { data } = await httpClient.get<ReviewsPage>('/organization/reviews', { params: { page } })
    return data
  },
}
