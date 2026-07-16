<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Support\Health\ApplicationHealth;
use Illuminate\Http\JsonResponse;

final readonly class HealthController
{
    public function __construct(private ApplicationHealth $health) {}

    public function __invoke(): JsonResponse
    {
        $result = $this->health->check();

        return response()->json(
            $result,
            $result['status'] === 'healthy' ? 200 : 503,
        );
    }
}
