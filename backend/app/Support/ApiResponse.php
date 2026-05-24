<?php

namespace App\Support;

use App\Enums\ApiErrorCode;
use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function error(ApiErrorCode $code, int $status): JsonResponse
    {
        return response()->json([
            'message' => $code->message(),
            'code' => $code->value,
        ], $status);
    }
}
