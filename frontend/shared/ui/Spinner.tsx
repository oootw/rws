type SpinnerSize = 'sm' | 'md';

type SpinnerProps = {
  size?: SpinnerSize;
  className?: string;
  label?: string;
};

const sizeClass: Record<SpinnerSize, string> = {
  sm: 'h-4 w-4 border-2',
  md: 'h-5 w-5 border-2',
};

const cx = (...parts: Array<string | false | undefined>): string =>
  parts.filter(Boolean).join(' ');

export function Spinner({ size = 'sm', className, label }: SpinnerProps) {
  return (
    <span
      role="status"
      aria-live="polite"
      aria-label={label ?? 'Загрузка'}
      className={cx(
        'inline-block animate-spin rounded-full border-current border-t-transparent align-[-2px]',
        sizeClass[size],
        className,
      )}
    />
  );
}
