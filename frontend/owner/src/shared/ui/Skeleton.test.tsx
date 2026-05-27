import { describe, expect, it } from 'vitest';
import { render } from '@testing-library/react';

import { Skeleton, SkeletonText } from './Skeleton';

describe('Skeleton', () => {
  it('рендерит aria-hidden плейсхолдер с pulse-классом', () => {
    const { container } = render(<Skeleton />);
    const node = container.firstChild as HTMLElement;
    expect(node.getAttribute('aria-hidden')).toBe('true');
    expect(node.className).toContain('animate-pulse');
  });

  it('SkeletonText рендерит указанное число строк с aria-busy', () => {
    const { container } = render(<SkeletonText lines={4} />);
    const wrapper = container.firstChild as HTMLElement;
    expect(wrapper.getAttribute('aria-busy')).toBe('true');
    expect(wrapper.children).toHaveLength(4);
  });

  it('SkeletonText по умолчанию рендерит 3 строки', () => {
    const { container } = render(<SkeletonText />);
    const wrapper = container.firstChild as HTMLElement;
    expect(wrapper.children).toHaveLength(3);
  });
});
