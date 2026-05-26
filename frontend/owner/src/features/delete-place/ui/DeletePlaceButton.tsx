import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Trash2 } from 'lucide-react';

import { Button, ConfirmDialog } from '@/shared/ui';

import { useDeletePlaceMutation } from '../api/useDeletePlaceMutation';

type DeletePlaceButtonProps = {
  placeId: string;
  placeTitle: string;
};

export function DeletePlaceButton({ placeId, placeTitle }: DeletePlaceButtonProps) {
  const [isOpen, setIsOpen] = useState(false);
  const navigate = useNavigate();
  const mutation = useDeletePlaceMutation();

  const onConfirm = () =>
    mutation.mutate(placeId, {
      onSuccess: () => {
        setIsOpen(false);
        navigate('/places', { replace: true });
      },
    });

  return (
    <>
      <Button variant="ghost" className="text-danger" onClick={() => setIsOpen(true)}>
        <Trash2 className="h-4 w-4" />
        Удалить
      </Button>
      <ConfirmDialog
        open={isOpen}
        title="Удалить точку?"
        description={
          <>
            «{placeTitle}» и все её отзывы будут удалены. Это действие нельзя отменить.
          </>
        }
        confirmLabel="Удалить"
        isPending={mutation.isPending}
        onCancel={() => (mutation.isPending ? null : setIsOpen(false))}
        onConfirm={onConfirm}
      />
    </>
  );
}
