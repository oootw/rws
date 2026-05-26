import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';

import { ReviewCard } from './ReviewCard';
import type { Review } from '../model/types';

const sampleReview: Review = {
  id: 'r-1',
  place_id: 'p-1',
  place_title: 'Уютное кафе',
  stars: 2,
  status: 'new',
  contact: '+7 (999) 000-00-00',
  text: 'Кофе остыл, ждали 30 минут.',
  created_at: '2026-05-26T10:00:00Z',
};

describe('ReviewCard', () => {
  it('показывает заголовок точки, статус и текст', () => {
    render(<ReviewCard review={sampleReview} />);

    expect(screen.getByText('Уютное кафе')).toBeInTheDocument();
    expect(screen.getByText('Новый')).toBeInTheDocument();
    expect(screen.getByText(/Кофе остыл/)).toBeInTheDocument();
    expect(screen.getByText(/\+7/)).toBeInTheDocument();
  });
});
