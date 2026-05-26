import type { PaginationMeta } from '@/entities/review';
import { Button } from '@/shared/ui';

type PaginationProps = {
  meta: PaginationMeta;
  onPageChange: (page: number) => void;
};

export function Pagination({ meta, onPageChange }: PaginationProps) {
  if (meta.last_page <= 1) {
    return null;
  }

  const canPrev = meta.page > 1;
  const canNext = meta.page < meta.last_page;

  return (
    <nav className="flex items-center justify-between gap-2" aria-label="Постраничная навигация">
      <Button
        variant="ghost"
        disabled={!canPrev}
        onClick={() => canPrev && onPageChange(meta.page - 1)}
      >
        Назад
      </Button>
      <span className="text-sm text-ink-500">
        Страница {meta.page} из {meta.last_page} · {meta.total} отзывов
      </span>
      <Button
        variant="ghost"
        disabled={!canNext}
        onClick={() => canNext && onPageChange(meta.page + 1)}
      >
        Дальше
      </Button>
    </nav>
  );
}
