<?php

use App\Http\Controllers\Api\Public\CriticalErrorController;
use App\Http\Controllers\Api\Public\PlaceController;
use App\Http\Controllers\Api\Public\RedirectController;
use App\Http\Controllers\Api\Public\ScanController;
use App\Interface\Http\Controllers\Internal\TlsAllowController;
use App\Interface\Http\Controllers\Owner\Auth\ExchangeOwnerLoginCodeController;
use App\Interface\Http\Controllers\Owner\Auth\LogoutOwnerController;
use App\Interface\Http\Controllers\Owner\OwnerDashboardController;
use App\Interface\Http\Controllers\Owner\OwnerFeaturesController;
use App\Interface\Http\Controllers\Owner\OwnerMeController;
use App\Interface\Http\Controllers\Owner\OwnerPlacesController;
use App\Interface\Http\Controllers\Owner\OwnerProfileController;
use App\Interface\Http\Controllers\Owner\OwnerReviewsController;
use App\Interface\Http\Controllers\Owner\OwnerSubscriptionController;
use App\Interface\Http\Controllers\Public\SubmitReviewController;
use App\Interface\Http\Controllers\Webhook\TelegramWebhookController;
use App\Interface\Http\Controllers\Webhook\TinkoffWebhookController;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('internal/tls-allow', TlsAllowController::class);

Route::post('webhooks/telegram', TelegramWebhookController::class);
Route::post('webhooks/tinkoff', TinkoffWebhookController::class);

Route::prefix('public')
    ->middleware(['tenant'])
    ->group(function (): void {
        Route::post('places/{place}/critical-error', [CriticalErrorController::class, 'store'])
            ->middleware(['resolve.public.place']);

        Route::middleware(['subscription.active', 'resolve.public.place'])->group(function (): void {
            Route::get('places/{place}', [PlaceController::class, 'show']);
            Route::post('places/{place}/scan', [ScanController::class, 'store']);
            Route::post('places/{place}/redirect', [RedirectController::class, 'store']);
            Route::post('places/{place}/reviews', SubmitReviewController::class)
                ->middleware('throttle:10,1');
        });
    });

/*
|--------------------------------------------------------------------------
| Owner-панель API
|--------------------------------------------------------------------------
|
| Sanctum SPA-аутентификация: cookies + session + CSRF. `EnsureFrontendRequestsAreStateful`
| оборачивает запросы со SPA-доменов в session middleware группу.
| После Фазы 1: /auth/exchange (без auth) и /me, /logout (auth:owner).
|
*/
Route::prefix('owner')
    ->middleware([
        EnsureFrontendRequestsAreStateful::class,
        EncryptCookies::class,
        StartSession::class,
        ValidateCsrfToken::class,
        'tenant',
        'tenant-owns-session',
    ])
    ->group(function (): void {
        Route::post('auth/exchange', ExchangeOwnerLoginCodeController::class)
            ->middleware('throttle:10,1');

        Route::middleware('auth:owner')->group(function (): void {
            // Всегда доступно: auth/служебное/чтение/оплата/профиль.
            Route::get('me', OwnerMeController::class);
            Route::post('auth/logout', LogoutOwnerController::class);

            Route::get('dashboard', OwnerDashboardController::class);

            Route::get('features', OwnerFeaturesController::class);

            Route::get('places', [OwnerPlacesController::class, 'index']);
            Route::get('places/charge-preview', [OwnerPlacesController::class, 'chargePreview']);
            Route::get('places/{place}', [OwnerPlacesController::class, 'show']);

            Route::get('reviews', [OwnerReviewsController::class, 'index']);

            Route::get('subscription', [OwnerSubscriptionController::class, 'show']);
            Route::post('subscription/init-payment', [OwnerSubscriptionController::class, 'initPayment']);
            Route::get('payments', [OwnerSubscriptionController::class, 'payments']);

            Route::patch('profile', [OwnerProfileController::class, 'update']);
            Route::post('profile/telegram/issue-code', [OwnerProfileController::class, 'issueTelegramCode'])
                ->middleware('throttle:5,1');

            // Платные мутации: требуют активной подписки. 402 + subscription_expired.
            // Порядок гардов: subscription.active (402) → feature (403). Сначала
            // деньги — потом availability фичи, иначе UX «фича недоступна» спрячет
            // настоящую проблему (отсутствие оплаты).
            Route::middleware('subscription.active:402')->group(function (): void {
                Route::post('places', [OwnerPlacesController::class, 'store'])
                    ->middleware('feature:multiple_places');
                Route::patch('places/{place}', [OwnerPlacesController::class, 'update']);
                Route::post('places/{place}/toggle', [OwnerPlacesController::class, 'toggle']);
                Route::delete('places/{place}', [OwnerPlacesController::class, 'destroy']);

                Route::patch('reviews/{review}/status', [OwnerReviewsController::class, 'changeStatus']);
            });
        });
    });
