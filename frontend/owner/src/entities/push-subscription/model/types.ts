/**
 * Конфиг Web Push с бэка: VAPID public key и фича-флаг.
 * `enabled=false` → бэк не сконфигурирован (нет VAPID ключей), UI скрывает
 * кнопку.
 */
export type PushConfig = {
  vapid_public_key: string;
  enabled: boolean;
};

/**
 * Запись подписки в БД (видимая владельцу).
 * `endpoint` уникален и используется как идентификатор «отозвать на устройстве».
 */
export type PushSubscriptionDevice = {
  id: string;
  endpoint: string;
  user_agent: string | null;
  created_at: string;
  last_seen_at: string | null;
};
