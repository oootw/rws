import { useMutation } from '@tanstack/react-query';
import type { UseMutationResult } from '@tanstack/react-query';

import { ensureCsrf, httpClient } from '@/shared/api';

type InitPaymentResponse = { data: { payment_url: string } };

const initPayment = async (): Promise<string> => {
  await ensureCsrf();
  const response = await httpClient.post<InitPaymentResponse>('/subscription/init-payment');
  return response.data.data.payment_url;
};

export const useInitPaymentMutation = (): UseMutationResult<string, Error, void> =>
  useMutation({
    mutationFn: initPayment,
    onSuccess: (paymentUrl) => {
      window.location.href = paymentUrl;
    },
  });
