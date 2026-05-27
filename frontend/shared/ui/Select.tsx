import { forwardRef } from 'react';
import type { SelectHTMLAttributes } from 'react';

type SelectProps = SelectHTMLAttributes<HTMLSelectElement>;

const base =
  'w-full appearance-none rounded-xl border border-ink-200 bg-surface px-3.5 py-2.5 pr-9 text-sm text-ink-900 ' +
  'transition focus:border-accent focus:outline-none focus:ring-4 focus:ring-accent/15 ' +
  'disabled:cursor-not-allowed disabled:opacity-60';

const cx = (...parts: Array<string | false | undefined>): string =>
  parts.filter(Boolean).join(' ');

export const Select = forwardRef<HTMLSelectElement, SelectProps>(
  ({ className, children, ...rest }, ref) => (
    <div className="relative">
      <select ref={ref} className={cx(base, className)} {...rest}>
        {children}
      </select>
      <span
        aria-hidden="true"
        className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-ink-400"
      >
        ▾
      </span>
    </div>
  ),
);
Select.displayName = 'Select';
