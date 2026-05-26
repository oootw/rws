<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Owner\Auth;

use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LogoutOwnerController
{
    public function __construct(
        private readonly AuthManager $auth,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->auth->guard('owner')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['data' => ['logged_out' => true]]);
    }
}
