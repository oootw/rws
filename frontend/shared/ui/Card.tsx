import { createElement } from 'react';
import type { HTMLAttributes, ReactNode } from 'react';

type CardPadding = 'none' | 'md';

type CardProps = HTMLAttributes<HTMLElement> & {
  as?: 'div' | 'article' | 'section';
  padding?: CardPadding;
  children: ReactNode;
};

const paddingClass: Record<CardPadding, string> = {
  none: '',
  md: 'p-5 sm:p-6',
};

const cx = (...parts: Array<string | false | undefined>): string =>
  parts.filter(Boolean).join(' ');

export function Card({
  as = 'div',
  padding = 'md',
  className,
  children,
  ...rest
}: CardProps) {
  return createElement(
    as,
    {
      ...rest,
      className: cx(
        'bg-surface shadow-soft rounded-2xl',
        paddingClass[padding],
        className,
      ),
    },
    children,
  );
}
