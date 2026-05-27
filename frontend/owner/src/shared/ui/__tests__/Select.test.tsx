import { describe, expect, it } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import { useState } from 'react';

import { Select } from '@guard-reviews/shared/ui';

function ControlledSelect() {
  const [value, setValue] = useState('a');
  return (
    <Select aria-label="opt" value={value} onChange={(event) => setValue(event.target.value)}>
      <option value="a">A</option>
      <option value="b">B</option>
    </Select>
  );
}

describe('Select', () => {
  it('рендерит native select с переданными option', () => {
    render(
      <Select aria-label="x" defaultValue="b">
        <option value="a">A</option>
        <option value="b">B</option>
      </Select>,
    );
    const node = screen.getByLabelText('x') as HTMLSelectElement;
    expect(node.tagName).toBe('SELECT');
    expect(node.value).toBe('b');
  });

  it('контролируемый onChange обновляет значение', () => {
    render(<ControlledSelect />);
    const node = screen.getByLabelText('opt') as HTMLSelectElement;
    fireEvent.change(node, { target: { value: 'b' } });
    expect(node.value).toBe('b');
  });
});
