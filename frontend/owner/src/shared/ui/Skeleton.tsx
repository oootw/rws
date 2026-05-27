import type { HTMLAttributes } from 'react';

type SkeletonProps = HTMLAttributes<HTMLDivElement>;

const base = 'animate-pulse rounded-md bg-ink-100';

const cx = (...parts: Array<string | false | undefined>): string =>
  parts.filter(Boolean).join(' ');

/**
 * Полоса-заглушка для отображения состояния загрузки.
 * По умолчанию занимает полную ширину родителя и имеет высоту 1rem.
 * Размеры/радиусы переопределяются через className.
 */
export function Skeleton({ className, ...rest }: SkeletonProps) {
  return (
    <div
      aria-hidden="true"
      className={cx(base, 'h-4 w-full', className)}
      {...rest}
    />
  );
}

type SkeletonTextProps = {
  lines?: number;
  className?: string;
};

/**
 * Несколько полос-заглушек как абзац текста.
 * Последняя строка чуть короче, чтобы выглядело естественно.
 */
export function SkeletonText({ lines = 3, className }: SkeletonTextProps) {
  return (
    <div className={cx('space-y-2', className)} aria-busy="true" aria-live="polite">
      {Array.from({ length: lines }).map((_, index) => (
        <Skeleton
          key={index}
          className={index === lines - 1 ? 'w-2/3' : undefined}
        />
      ))}
    </div>
  );
}
