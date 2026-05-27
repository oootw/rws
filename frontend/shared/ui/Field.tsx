import type { ReactNode } from 'react';

type FieldProps = {
  label: string;
  htmlFor: string;
  error?: string;
  hint?: string;
  className?: string;
  children: ReactNode;
};

const cx = (...parts: Array<string | false | undefined>): string =>
  parts.filter(Boolean).join(' ');

export function Field({ label, htmlFor, error, hint, className, children }: FieldProps) {
  const errorId = error !== undefined ? `${htmlFor}-error` : undefined;
  const hintId = hint !== undefined ? `${htmlFor}-hint` : undefined;
  return (
    <div className={cx('flex flex-col gap-1.5', className)}>
      <label htmlFor={htmlFor} className="text-xs font-medium text-ink-500">
        {label}
      </label>
      {children}
      {hint !== undefined && error === undefined && (
        <p id={hintId} className="text-xs text-ink-500">
          {hint}
        </p>
      )}
      {error !== undefined && (
        <p id={errorId} role="alert" className="text-xs text-danger">
          {error}
        </p>
      )}
    </div>
  );
}
