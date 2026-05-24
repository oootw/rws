<?php

use App\Http\Controllers\Api\Public\CriticalErrorController;
use App\Http\Controllers\Api\Public\PlaceController;
use App\Http\Controllers\Api\Public\RedirectController;
use App\Http\Controllers\Api\Public\ScanController;
use App\Interface\Http\Controllers\Internal\TlsAllowController;
use App\Interface\Http\Controllers\Public\SubmitReviewController;
use App\Interface\Http\Controllers\Webhook\TelegramWebhookController;
use App\Interface\Http\Controllers\Webhook\TinkoffWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
