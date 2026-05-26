<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Interface\Filament\Http\RestrictAdminToAllowedIps;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Composition root админ-панели Guard Reviews.
 *
 * Ключевые решения:
 *  - монтируем на `/admin` (см. config/guardreviews.php admin.path);
 *  - используем собственный guard `admin` (см. config/auth.php),
 *    backed by EnvUserProvider — никакой таблицы users для админа;
 *  - брендинг и русская локаль для всех страниц панели;
 *  - Resource'ы автодискаверятся из app/Filament/Resources/,
 *    Pages — из app/Filament/Pages/, Widgets — из app/Filament/Widgets/.
 */
final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path((string) config('guardreviews.admin.path', 'admin'))
            ->login()
            ->authGuard('admin')
            ->brandName('Guard Reviews · Admin')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                RestrictAdminToAllowedIps::class,
                ThrottleRequests::class.':'.((string) config('guardreviews.admin.throttle', '60,1')),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
