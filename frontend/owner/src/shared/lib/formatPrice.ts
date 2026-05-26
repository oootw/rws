const rubleFormatter = new Intl.NumberFormat('ru-RU', {
  style: 'currency',
  currency: 'RUB',
  maximumFractionDigits: 0,
});

/**
 * Преобразует копейки → «1 234 ₽». Округление до целого рубля.
 */
export const formatKopecks = (kopecks: number): string =>
  rubleFormatter.format(Math.round(kopecks / 100));
