<?php

declare(strict_types=1);

namespace App\Infrastructure\ServiceProviders;

use App\Application\Reviews\Listeners\NotifyOwnerAboutNegativeReview;
use App\Application\Reviews\ListRecentReviewsForOwner\RecentReviewsReader;
use App\Application\Shared\Events\DomainEventDispatcher;
use App\Domain\Reviews\Events\NegativeReviewSubmitted;
use App\Domain\Reviews\ReviewIdGenerator;
use App\Domain\Reviews\ReviewRepository;
use App\Domain\Shared\Clock\Clock;
use App\Infrastructure\Clock\SystemClock;
use App\Infrastructure\Events\LaravelDomainEventDispatcher;
use App\Infrastructure\Persistence\Eloquent\Reviews\EloquentRecentReviewsReader;
use App\Infrastructure\Persistence\Eloquent\Reviews\EloquentReviewRepository;
use App\Infrastructure\Persistence\Eloquent\Reviews\UuidReviewIdGenerator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

/**
 * Composition root контекста Reviews:
 *  - связывает доменные порты с инфраструктурными адаптерами;
 *  - регистрирует слушателей доменных событий.
 */
final class ReviewsServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        Clock::class => SystemClock::class,
        ReviewRepository::class => EloquentReviewRepository::class,
        ReviewIdGenerator::class => UuidReviewIdGenerator::class,
        RecentReviewsReader::class => EloquentRecentReviewsReader::class,
        DomainEventDispatcher::class => LaravelDomainEventDispatcher::class,
    ];

    public function boot(Dispatcher $events): void
    {
        $events->listen(NegativeReviewSubmitted::class, [NotifyOwnerAboutNegativeReview::class, 'handle']);
    }
}
