import { forwardRef } from 'react';
import type { TextareaHTMLAttributes } from 'react';

type TextareaProps = TextareaHTMLAttributes<HTMLTextAreaElement>;

const base =
  'w-full rounded-xl border border-ink-200 bg-surface px-3.5 py-2.5 text-sm text-ink-900 ' +
  'placeholder:text-ink-400 transition focus:border-accent focus:outline-none focus:ring-4 focus:ring-accent/15 ' +
  'disabled:cursor-not-allowed disabled:opacity-60 resize-y';

const cx = (...parts: Array<string | false | undefined>): string =>
  parts.filter(Boolean).join(' ');

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(
  ({ className, rows = 4, ...rest }, ref) => (
    <textarea ref={ref} rows={rows} className={cx(base, className)} {...rest} />
  ),
);
Textarea.displayName = 'Textarea';
