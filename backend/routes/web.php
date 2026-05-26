<?php

use App\Interface\Http\Controllers\Owner\OwnerSpaController;
use App\Interface\Http\Controllers\Public\PaymentResultController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response([
        'isHealthy' => true,
    ]);
});

Route::redirect('/privacy', '/privacy/index.html');

Route::get('/payment/success', [PaymentResultController::class, 'success']);
Route::get('/payment/fail', [PaymentResultController::class, 'fail']);

/*
|--------------------------------------------------------------------------
| Owner-панель (SPA)
|--------------------------------------------------------------------------
|
| Монтируется на `/owner` и любые подпути (SPA сам рулит навигацию).
| Требует валидного тенанта в поддомене (см. middleware `tenant`).
| Реализация фронта — frontend/owner/ (React + Vite + PWA).
|
*/
Route::middleware('tenant')->group(function (): void {
    Route::get('/owner', OwnerSpaController::class);
    Route::get('/owner/{any}', OwnerSpaController::class)->where('any', '.*');
});
