import type { PaymentStatus } from '../model/types';

const labels: Record<PaymentStatus, string> = {
  pending: 'Ожидает оплаты',
  success: 'Оплачено',
  failed: 'Ошибка',
  refunded: 'Возврат',
};

export const paymentStatusLabel = (status: PaymentStatus): string => labels[status];
