import { Outlet } from 'react-router-dom';

/**
 * Лэйаут для /login и других unauthenticated-страниц.
 * Минимум хрома — только бренд и слот.
 */
export function AuthLayout() {
  return (
    <div className="flex min-h-screen flex-col bg-canvas">
      <header className="px-6 py-6">
        <span className="text-lg font-semibold tracking-tight">Guard Reviews</span>
      </header>
      <main className="flex flex-1 items-center justify-center px-4 pb-12 sm:px-6">
        <Outlet />
      </main>
    </div>
  );
}
