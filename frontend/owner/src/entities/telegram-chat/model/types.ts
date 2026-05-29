/**
 * Привязанный групповой Telegram-чат — зеркало
 * `App\Interface\Http\Views\Owner\OwnerTelegramChatView::fromView()`.
 */
export type TelegramChat = {
  id: string;
  chat_id: string;
  title: string | null;
  linked_at: string;
};

/**
 * Ответ POST /telegram-chats/issue-link — зеркало
 * `OwnerTelegramChatView::fromIssuedToken()`.
 *
 * `deep_link` — `https://t.me/<bot>?startgroup=<token>`; UI открывает его в
 * Telegram, владелец выбирает чат, бот авто-привязывает по `/start <token>`.
 */
export type IssuedChatLink = {
  deep_link: string;
  expires_at: string;
};
