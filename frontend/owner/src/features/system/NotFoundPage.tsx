import { Link } from 'react-router-dom';

export function NotFoundPage() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-canvas p-6">
      <div className="card-padded max-w-md text-center">
        <h1 className="text-2xl font-semibold tracking-tight">Страница не найдена</h1>
        <p className="mt-2 text-sm text-ink-500">
          Возможно, вы перешли по устаревшей ссылке.
        </p>
        <Link to="/" className="btn-primary mt-6 inline-flex">
          На главную
        </Link>
      </div>
    </div>
  );
}
