<?php

declare(strict_types=1);

namespace App\Support\Api;

final class OpenApiDocument
{
    /** @return array<string, mixed> */
    public function build(): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Express Cloud API',
                'version' => '1.0.0',
                'description' => 'Versioned operational API for products, customers, sales, quotes, and reports.',
            ],
            'servers' => [
                ['url' => url('/api/v1')],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'ExpressCloudToken',
                    ],
                ],
            ],
            'security' => [
                ['bearerAuth' => []],
            ],
            'paths' => [
                '/products' => [
                    'get' => [
                        'summary' => 'List products',
                        'responses' => [
                            '200' => ['description' => 'Product collection'],
                        ],
                    ],
                ],
                '/customers' => [
                    'get' => [
                        'summary' => 'List customers',
                        'responses' => [
                            '200' => ['description' => 'Customer collection'],
                        ],
                    ],
                ],
                '/sales' => [
                    'get' => [
                        'summary' => 'List sales and quotes',
                        'responses' => [
                            '200' => ['description' => 'Sales collection'],
                        ],
                    ],
                ],
                '/reports/sales-by-branch' => [
                    'get' => [
                        'summary' => 'Sales totals by branch',
                        'responses' => [
                            '200' => ['description' => 'Branch totals'],
                        ],
                    ],
                ],
                '/reports/low-stock' => [
                    'get' => [
                        'summary' => 'Open low-stock records',
                        'responses' => [
                            '200' => ['description' => 'Low-stock collection'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
