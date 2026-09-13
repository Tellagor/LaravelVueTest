export type OrganizationStatus = 'pending' | 'processing' | 'ready' | 'failed'

export interface Organization {
  id: number
  user_id: number
  yandex_url: string
  yandex_id: string
  name: string | null
  rating_avg: string | null
  ratings_count: number | null
  reviews_count: number | null
  status: OrganizationStatus
  last_error: string | null
  last_parsed_at: string | null
}
