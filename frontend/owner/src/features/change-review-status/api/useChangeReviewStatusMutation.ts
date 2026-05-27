import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationResult } from '@tanstack/react-query';

import { ensureCsrf, httpClient } from '@/shared/api';
import { reviewsQueryKeys } from '@/entities/review';
import type { ReviewStatus, ReviewsPage } from '@/entities/review';

type ChangeArgs = { reviewId: string; status: ReviewStatus };

type ReviewListSnapshot = { queryKey: readonly unknown[]; data: ReviewsPage };

const patchStatus = async ({ reviewId, status }: ChangeArgs): Promise<void> => {
  await ensureCsrf();
  await httpClient.patch(`/reviews/${reviewId}/status`, { status });
};

const applyStatus = (
  page: ReviewsPage,
  reviewId: string,
  status: ReviewStatus,
): ReviewsPage => ({
  ...page,
  items: page.items.map((review) =>
    review.id === reviewId ? { ...review, status } : review,
  ),
});

export const useChangeReviewStatusMutation = (): UseMutationResult<
  void,
  Error,
  ChangeArgs,
  { snapshots: ReviewListSnapshot[] }
> => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: patchStatus,
    onMutate: ({ reviewId, status }) => {
      const entries = queryClient.getQueriesData<ReviewsPage>({
        queryKey: reviewsQueryKeys.all,
      });

      const snapshots: ReviewListSnapshot[] = [];
      for (const [queryKey, data] of entries) {
        if (data === undefined) continue;
        snapshots.push({ queryKey, data });
        queryClient.setQueryData<ReviewsPage>(queryKey, applyStatus(data, reviewId, status));
      }

      return { snapshots };
    },
    onError: (_error, _vars, context) => {
      if (context === undefined) return;
      for (const { queryKey, data } of context.snapshots) {
        queryClient.setQueryData(queryKey, data);
      }
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: reviewsQueryKeys.all });
    },
  });
};
