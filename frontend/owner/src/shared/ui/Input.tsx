import { forwardRef } from 'react';
import type { InputHTMLAttributes } from 'react';

type InputProps = InputHTMLAttributes<HTMLInputElement>;

const base =
  'w-full rounded-xl border border-ink-200 bg-surface px-3.5 py-2.5 text-sm text-ink-900 ' +
  'placeholder:text-ink-400 transition focus:border-accent focus:outline-none focus:ring-4 focus:ring-accent/15';

const cx = (...parts: Array<string | false | undefined>): string =>
  parts.filter(Boolean).join(' ');

export const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ className, ...rest }, ref) => (
    <input ref={ref} className={cx(base, className)} {...rest} />
  ),
);
Input.displayName = 'Input';
