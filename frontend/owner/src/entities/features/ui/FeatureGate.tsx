import type { ReactNode } from 'react';

import { useFeature } from '../model/useFeature';
import type { Feature } from '../model/types';

type FeatureGateProps = {
  feature: Feature;
  fallback?: ReactNode;
  children: ReactNode;
};

/**
 * Условно рендерит children, если у владельца есть фича; иначе fallback.
 * Чтобы избежать flash-of-visible-content при загрузке кэша — оборачиваем
 * вокруг `<UpsellCard>` или `null`. На самом запросе UX-приоритет: 402 (нет
 * подписки) > 403 (нет фичи), backend сам выбирает правильный.
 */
export function FeatureGate({ feature, fallback = null, children }: FeatureGateProps) {
  return useFeature(feature) ? <>{children}</> : <>{fallback}</>;
}
