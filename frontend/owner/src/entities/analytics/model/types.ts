export type DailyMetric = {
  date: string;
  scans: number;
  reviews: number;
};

export type DashboardSnapshot = {
  scans: number;
  reviews: number;
  negative: number;
  redirects: number;
  places_count: number;
  daily_series: DailyMetric[];
};
