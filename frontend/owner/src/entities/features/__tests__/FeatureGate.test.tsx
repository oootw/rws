import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import { FeatureGate, featuresQueryKeys, UpsellCard } from '@/entities/features';
import type { Feature } from '@/entities/features';

function renderGate(grantedFeatures: Feature[], gateFeature: Feature) {
  const client = new QueryClient();
  client.setQueryData(featuresQueryKeys.list(), new Set(grantedFeatures));
  return render(
    <QueryClientProvider client={client}>
      <MemoryRouter>
        <FeatureGate feature={gateFeature} fallback={<UpsellCard />}>
          <p>granted content</p>
        </FeatureGate>
      </MemoryRouter>
    </QueryClientProvider>,
  );
}

describe('FeatureGate', () => {
  it('рисует children, если фича есть в кэше', () => {
    renderGate(['multiple_places'], 'multiple_places');
    expect(screen.getByText('granted content')).toBeInTheDocument();
    expect(screen.queryByText(/Обновите подписку/i)).not.toBeInTheDocument();
  });

  it('рисует fallback (UpsellCard), если фичи нет', () => {
    renderGate([], 'multiple_places');
    expect(screen.queryByText('granted content')).not.toBeInTheDocument();
    expect(screen.getByText(/Обновите подписку/i)).toBeInTheDocument();
  });

  it('рисует fallback при пустом fallback по умолчанию', () => {
    const client = new QueryClient();
    client.setQueryData(featuresQueryKeys.list(), new Set<Feature>([]));
    render(
      <QueryClientProvider client={client}>
        <FeatureGate feature="api_access">
          <p>granted</p>
        </FeatureGate>
      </QueryClientProvider>,
    );
    expect(screen.queryByText('granted')).not.toBeInTheDocument();
  });
});
