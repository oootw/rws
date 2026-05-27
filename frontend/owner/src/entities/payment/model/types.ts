export type PaymentStatus = 'pending' | 'success' | 'failed' | 'refunded';

export type OwnerPayment = {
  id: string;
  amount: number;
  status: PaymentStatus;
  external_id: string | null;
  tariff_title: string | null;
  created_at: string;
};

export type PaginationMeta = {
  total: number;
  page: number;
  per_page: number;
  last_page: number;
};

export type OwnerPaymentsPage = {
  items: OwnerPayment[];
  meta: PaginationMeta;
};

export type OwnerPaymentsFilters = {
  page?: number;
  perPage?: number;
};
