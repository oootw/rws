import { useEffect, useRef, useState } from 'react';
import { Check, ChevronDown } from 'lucide-react';

import { statusLabel } from '@/entities/review';
import type { ReviewStatus } from '@/entities/review';

import { useChangeReviewStatusMutation } from '../api/useChangeReviewStatusMutation';
import { availableTransitions } from '../lib/transitions';

type StatusSwitcherProps = {
  reviewId: string;
  current: ReviewStatus;
};

const triggerClass =
  'inline-flex items-center gap-1 rounded-full bg-ink-100 px-2.5 py-0.5 text-xs ' +
  'font-medium text-ink-700 transition hover:bg-ink-200 ' +
  'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 ' +
  'focus-visible:outline-accent disabled:cursor-not-allowed disabled:opacity-60';

export function StatusSwitcher({ reviewId, current }: StatusSwitcherProps) {
  const [open, setOpen] = useState(false);
  const wrapperRef = useRef<HTMLDivElement>(null);
  const mutation = useChangeReviewStatusMutation();
  const options = availableTransitions(current);

  useEffect(() => {
    if (!open) return;

    const handleClick = (event: MouseEvent) => {
      if (!wrapperRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    };
    const handleKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') setOpen(false);
    };

    document.addEventListener('mousedown', handleClick);
    document.addEventListener('keydown', handleKey);
    return () => {
      document.removeEventListener('mousedown', handleClick);
      document.removeEventListener('keydown', handleKey);
    };
  }, [open]);

  const select = (status: ReviewStatus): void => {
    setOpen(false);
    if (status === current) return;
    mutation.mutate({ reviewId, status });
  };

  return (
    <div ref={wrapperRef} className="relative inline-block">
      <button
        type="button"
        className={triggerClass}
        onClick={() => setOpen((v) => !v)}
        disabled={mutation.isPending}
        aria-haspopup="listbox"
        aria-expanded={open}
        aria-label="Изменить статус отзыва"
      >
        {statusLabel(current)}
        <ChevronDown className="h-3 w-3" aria-hidden />
      </button>

      {open && (
        <ul
          role="listbox"
          className="absolute right-0 z-20 mt-1 min-w-[10rem] overflow-hidden rounded-xl border border-ink-100 bg-white py-1 shadow-soft"
        >
          {options.map((status) => (
            <li key={status}>
              <button
                type="button"
                role="option"
                aria-selected={false}
                className="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm text-ink-700 hover:bg-ink-100"
                onClick={() => select(status)}
              >
                {statusLabel(status)}
                <Check className="h-3 w-3 opacity-0" aria-hidden />
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
