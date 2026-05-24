<?php

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
