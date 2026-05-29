/**
 * Зеркало backend enum `App\Domain\Iam\Feature`.
 *
 * Здесь поддерживаем вручную; sync-нарушение ловится тестом
 * (см. `__tests__/features-sync.test.ts`), который запрашивает enum-cases
 * с бэка и сравнивает со списком. Но в рантайме тип — закрытый union.
 */
export type Feature =
  | 'multiple_places'
  | 'weekly_digest'
  | 'negative_alerts_telegram'
  | 'negative_alerts_email'
  | 'custom_branding'
  | 'qr_themes'
  | 'csv_export_reviews'
  | 'api_access'
  | 'priority_support'
  | 'shared_telegram_chat';
