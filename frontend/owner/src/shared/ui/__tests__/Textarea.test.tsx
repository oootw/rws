import { describe, expect, it } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import { useState } from 'react';

import { Textarea } from '@guard-reviews/shared/ui';

function ControlledTextarea() {
  const [value, setValue] = useState('');
  return (
    <Textarea
      aria-label="comment"
      value={value}
      onChange={(event) => setValue(event.target.value)}
    />
  );
}

describe('Textarea', () => {
  it('рендерит textarea с дефолтными rows=4', () => {
    render(<Textarea aria-label="x" />);
    const node = screen.getByLabelText('x') as HTMLTextAreaElement;
    expect(node.tagName).toBe('TEXTAREA');
    expect(node.rows).toBe(4);
  });

  it('контролируемый ввод обновляет значение', () => {
    render(<ControlledTextarea />);
    const node = screen.getByLabelText('comment') as HTMLTextAreaElement;
    fireEvent.change(node, { target: { value: 'hi' } });
    expect(node.value).toBe('hi');
  });
});
