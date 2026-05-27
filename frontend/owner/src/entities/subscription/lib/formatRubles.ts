/**
 * Конвертирует копейки в строку «N ₽» (без копеек если делится нацело).
 */
export const formatRubles = (kopecks: number): string => {
  const rubles = kopecks / 100;
  const formatted = Number.isInteger(rubles)
    ? rubles.toLocaleString('ru-RU')
    : rubles.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  return `${formatted} ₽`;
};
