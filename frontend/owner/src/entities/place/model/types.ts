export type PlaceSummary = {
  id: string;
  title: string;
  platforms_count: number;
  is_active: boolean;
};

export type PlacePlatform = {
  type: string;
  url: string;
  label: string;
};

export type PlaceDetail = {
  id: string;
  title: string;
  is_active: boolean;
  background_image_url: string | null;
  scan_url: string;
  qr_png_url: string;
  platforms: PlacePlatform[];
};

export type PlaceInput = {
  title: string;
  background_image_url: string | null;
  platforms: PlacePlatform[];
};

export type PlaceCharge = {
  prorata_amount: number;
  days_left: number;
  monthly_delta: number;
  requires_payment: boolean;
};
