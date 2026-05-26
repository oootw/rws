import { Button } from '@/shared/ui';

import { useTogglePlaceActivationMutation } from '../api/useTogglePlaceActivationMutation';

type PlaceActivationToggleProps = {
  placeId: string;
  isActive: boolean;
};

export function PlaceActivationToggle({ placeId, isActive }: PlaceActivationToggleProps) {
  const mutation = useTogglePlaceActivationMutation();

  return (
    <Button
      variant="ghost"
      onClick={() => mutation.mutate({ placeId, active: !isActive })}
      disabled={mutation.isPending}
    >
      {isActive ? 'Выключить' : 'Включить'}
    </Button>
  );
}
