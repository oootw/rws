export type ReviewStatus = 'new' | 'in_progress' | 'resolved' | 'archived';

export type Review = {
  id: string;
  place_id: string;
  place_title: string;
  stars: number;
  status: ReviewStatus;
  contact: string;
  text: string;
  created_at: string;
};

export type ReviewsFilters = {
  status?: ReviewStatus;
  placeId?: string;
  from?: string;
  until?: string;
  page?: number;
  perPage?: number;
};

export type PaginationMeta = {
  total: number;
  page: number;
  per_page: number;
  last_page: number;
};

export type ReviewsPage = {
  items: Review[];
  meta: PaginationMeta;
};
