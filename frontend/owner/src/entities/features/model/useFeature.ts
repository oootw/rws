import { useFeaturesQuery } from '../api/useFeaturesQuery';
import type { Feature } from './types';

/**
 * UI-подсказка «доступна ли фича». До загрузки кэша возвращает `false`
 * (gated UI спрятан до ответа сервера). Авторитет — backend middleware
 * `feature:<key>`; этот hook только подсказывает, что нет смысла рисовать
 * кнопку, которая всё равно вернёт 403.
 */
export const useFeature = (flag: Feature): boolean => {
  const query = useFeaturesQuery();
  return query.data?.has(flag) ?? false;
};
