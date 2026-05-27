export type OwnerSubscription = {
  tariff_id: string | null;
  tariff_title: string | null;
  ends_at: string | null;
  days_left: number;
  is_active: boolean;
  places_used: number;
  places_limit: number | null;
  next_charge_amount: number;
};
