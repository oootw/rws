import { createElement } from 'react';
import type { HTMLAttributes, ReactNode } from 'react';

type StackDirection = 'row' | 'col';
type StackGap = 1 | 2 | 3 | 4 | 6 | 8;
type StackAlign = 'start' | 'center' | 'end' | 'stretch' | 'baseline';
type StackJustify = 'start' | 'center' | 'end' | 'between' | 'around';

type StackProps = HTMLAttributes<HTMLElement> & {
  as?: 'div' | 'section' | 'article' | 'header' | 'footer' | 'ul' | 'ol';
  direction?: StackDirection;
  gap?: StackGap;
  align?: StackAlign;
  justify?: StackJustify;
  wrap?: boolean;
  children: ReactNode;
};

const directionClass: Record<StackDirection, string> = {
  row: 'flex flex-row',
  col: 'flex flex-col',
};

const gapClass: Record<StackGap, string> = {
  1: 'gap-1',
  2: 'gap-2',
  3: 'gap-3',
  4: 'gap-4',
  6: 'gap-6',
  8: 'gap-8',
};

const alignClass: Record<StackAlign, string> = {
  start: 'items-start',
  center: 'items-center',
  end: 'items-end',
  stretch: 'items-stretch',
  baseline: 'items-baseline',
};

const justifyClass: Record<StackJustify, string> = {
  start: 'justify-start',
  center: 'justify-center',
  end: 'justify-end',
  between: 'justify-between',
  around: 'justify-around',
};

const cx = (...parts: Array<string | false | undefined>): string =>
  parts.filter(Boolean).join(' ');

export function Stack({
  as = 'div',
  direction = 'col',
  gap = 3,
  align,
  justify,
  wrap = false,
  className,
  children,
  ...rest
}: StackProps) {
  return createElement(
    as,
    {
      ...rest,
      className: cx(
        directionClass[direction],
        gapClass[gap],
        align !== undefined && alignClass[align],
        justify !== undefined && justifyClass[justify],
        wrap && 'flex-wrap',
        className,
      ),
    },
    children,
  );
}
