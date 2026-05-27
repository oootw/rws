import type { ReactNode } from 'react';

import { Card } from './Card';

type EmptyStateProps = {
  title: string;
  description?: string;
  icon?: ReactNode;
  action?: ReactNode;
  className?: string;
};

const cx = (...parts: Array<string | false | undefined>): string =>
  parts.filter(Boolean).join(' ');

/**
 * Унифицированное пустое состояние: иконка/иллюстрация + заголовок +
 * описание + опциональное действие. Используется когда список пуст.
 */
export function EmptyState({ title, description, icon, action, className }: EmptyStateProps) {
  return (
    <Card className={cx('flex flex-col items-center gap-3 text-center', className)}>
      {icon !== undefined && (
        <div className="text-ink-400" aria-hidden="true">
          {icon}
        </div>
      )}
      <h3 className="text-base font-semibold text-ink-900">{title}</h3>
      {description !== undefined && (
        <p className="text-sm text-ink-500">{description}</p>
      )}
      {action !== undefined && <div className="pt-1">{action}</div>}
    </Card>
  );
}
