type BuildTelegramDeepLinkOptions = {
  botUsername: string;
  payload?: string;
};

/**
 * Pure-функция для сборки deep-link на Telegram-бот.
 * Используется в LoginCta для кнопки «Открыть бот».
 */
export const buildTelegramDeepLink = ({
  botUsername,
  payload = 'login',
}: BuildTelegramDeepLinkOptions): string =>
  `https://t.me/${botUsername}?start=${encodeURIComponent(payload)}`;
