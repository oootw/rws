<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Payments;

use App\Application\Payments\ListOwnerPayments\OwnerPaymentsPage;
use App\Application\Payments\ListOwnerPayments\OwnerPaymentsReader;
use App\Application\Payments\ListOwnerPayments\OwnerPaymentView;
use App\Domain\Iam\OwnerId;
use App\Domain\Payments\PaymentStatus;
use App\Models\PaymentTransaction as PaymentTransactionModel;
use DateTimeImmutable;

final class EloquentOwnerPaymentsReader implements OwnerPaymentsReader
{
    public function paginate(OwnerId $ownerId, int $page, int $perPage): OwnerPaymentsPage
    {
        $query = PaymentTransactionModel::query()->where('user_id', $ownerId->value);

        $total = (clone $query)->count();

        $items = $query
            ->with('tariff:id,title')
            ->latest('created_at')
            ->forPage($page, $perPage)
            ->get()
            ->map(self::toView(...))
            ->values()
            ->all();

        return new OwnerPaymentsPage(
            items: $items,
            total: $total,
            page: $page,
            perPage: $perPage,
        );
    }

    private static function toView(PaymentTransactionModel $model): OwnerPaymentView
    {
        $status = $model->status instanceof PaymentStatus
            ? $model->status
            : PaymentStatus::from((string) $model->status);

        return new OwnerPaymentView(
            id: (string) $model->id,
            amount: (int) $model->amount,
            status: $status,
            externalId: $model->external_id !== null ? (string) $model->external_id : null,
            tariffTitle: $model->tariff?->title !== null ? (string) $model->tariff->title : null,
            createdAt: DateTimeImmutable::createFromInterface($model->created_at),
        );
    }
}
