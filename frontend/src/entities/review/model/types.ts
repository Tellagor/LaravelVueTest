export interface Review {
  id: number
  organization_id: number
  external_id: string
  author_name: string
  rating: number
  text: string | null
  published_at: string | null
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  total: number
}

export interface ReviewsPage {
  data: Review[]
  meta: PaginationMeta
}
