import { forwardRef } from 'react';
import type { ButtonHTMLAttributes } from 'react';

type ButtonVariant = 'primary' | 'ghost';

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: ButtonVariant;
};

const base =
  'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition ' +
  'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 ' +
  'disabled:cursor-not-allowed disabled:opacity-50';

const variants: Record<ButtonVariant, string> = {
  primary:
    'bg-accent text-accent-fg hover:brightness-105 active:brightness-95 focus-visible:outline-accent',
  ghost:
    'text-ink-700 hover:bg-ink-100 active:bg-ink-200 focus-visible:outline-ink-400',
};

const cx = (...parts: Array<string | false | undefined>): string =>
  parts.filter(Boolean).join(' ');

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  ({ variant = 'primary', type = 'button', className, ...rest }, ref) => (
    <button
      ref={ref}
      type={type}
      className={cx(base, variants[variant], className)}
      {...rest}
    />
  ),
);
Button.displayName = 'Button';
