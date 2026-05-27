import type { HTMLAttributes, ReactNode } from 'react';

export type BadgeTone = 'neutral' | 'accent' | 'danger' | 'warning' | 'success';

type BadgeProps = HTMLAttributes<HTMLSpanElement> & {
  tone?: BadgeTone;
  children: ReactNode;
};

const toneClass: Record<BadgeTone, string> = {
  neutral: 'bg-ink-100 text-ink-500',
  accent: 'bg-accent-soft text-accent',
  success: 'bg-accent-soft text-accent',
  danger: 'bg-danger/10 text-danger',
  warning: 'bg-warning/15 text-warning',
};

const base = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium';

const cx = (...parts: Array<string | false | undefined>): string =>
  parts.filter(Boolean).join(' ');

export function Badge({ tone = 'neutral', className, children, ...rest }: BadgeProps) {
  return (
    <span className={cx(base, toneClass[tone], className)} {...rest}>
      {children}
    </span>
  );
}
