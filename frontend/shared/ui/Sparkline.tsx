type SparklineProps = {
  values: number[];
  height?: number;
  ariaLabel?: string;
};

const VIEWBOX_WIDTH = 100;
const VIEWBOX_HEIGHT = 30;

/**
 * Минималистичный inline SVG. Никаких сторонних библиотек — это тонкий
 * read-only визуальный компонент.
 */
export function Sparkline({ values, height = 32, ariaLabel }: SparklineProps) {
  if (values.length === 0) {
    return <span aria-hidden className="text-xs text-ink-400">—</span>;
  }

  const max = Math.max(...values, 1);
  const step = VIEWBOX_WIDTH / Math.max(1, values.length - 1);

  const points = values
    .map((value, index) => {
      const x = index * step;
      const y = VIEWBOX_HEIGHT - (value / max) * VIEWBOX_HEIGHT;
      return `${x.toFixed(1)},${y.toFixed(1)}`;
    })
    .join(' ');

  return (
    <svg
      viewBox={`0 0 ${VIEWBOX_WIDTH} ${VIEWBOX_HEIGHT}`}
      role="img"
      aria-label={ariaLabel}
      style={{ height, width: '100%' }}
      preserveAspectRatio="none"
    >
      <polyline
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinejoin="round"
        strokeLinecap="round"
        points={points}
      />
    </svg>
  );
}
