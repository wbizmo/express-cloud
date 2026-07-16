<?php

declare(strict_types=1);

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /** @param array<string, mixed> $meta */
    public static function success(
        mixed $data,
        int $status = 200,
        array $meta = [],
    ): JsonResponse {
        return response()->json([
            'data' => $data,
            'meta' => (object) $meta,
        ], $status);
    }
}
