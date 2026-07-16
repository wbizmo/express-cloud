<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Support\Api\OpenApiDocument;
use Illuminate\Http\JsonResponse;

final readonly class OpenApiController
{
    public function __construct(private OpenApiDocument $document) {}

    public function __invoke(): JsonResponse
    {
        return response()->json($this->document->build());
    }
}
