import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';

import { App } from './App';

function renderAt(path: string) {
  const client = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });

  return render(
    <QueryClientProvider client={client}>
      <MemoryRouter initialEntries={[path]}>
        <App />
      </MemoryRouter>
    </QueryClientProvider>,
  );
}

describe('App routing', () => {
  it('рендерит дашборд на корне', () => {
    renderAt('/');
    expect(screen.getByText('Кабинет владельца')).toBeInTheDocument();
  });

  it('рендерит login-страницу на /login', () => {
    renderAt('/login');
    expect(screen.getByText('Вход в кабинет')).toBeInTheDocument();
  });

  it('рендерит 404 на неизвестных маршрутах', () => {
    renderAt('/this/route/does/not/exist');
    expect(screen.getByText('Страница не найдена')).toBeInTheDocument();
  });
});
