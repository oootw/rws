<?php

use App\Infrastructure\ServiceProviders\AnalyticsServiceProvider;
use App\Infrastructure\ServiceProviders\IamServiceProvider;
use App\Infrastructure\ServiceProviders\JobsServiceProvider;
use App\Infrastructure\ServiceProviders\NotificationsServiceProvider;
use App\Infrastructure\ServiceProviders\PaymentsServiceProvider;
use App\Infrastructure\ServiceProviders\PlacesServiceProvider;
use App\Infrastructure\ServiceProviders\ReviewsServiceProvider;
use App\Infrastructure\ServiceProviders\TelegramServiceProvider;
use App\Interface\Filament\Auth\AdminAuthServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;

return [
    AppServiceProvider::class,
    IamServiceProvider::class,
    AnalyticsServiceProvider::class,
    NotificationsServiceProvider::class,
    PaymentsServiceProvider::class,
    PlacesServiceProvider::class,
    ReviewsServiceProvider::class,
    TelegramServiceProvider::class,
    JobsServiceProvider::class,
    AdminAuthServiceProvider::class,
    AdminPanelProvider::class,
];
