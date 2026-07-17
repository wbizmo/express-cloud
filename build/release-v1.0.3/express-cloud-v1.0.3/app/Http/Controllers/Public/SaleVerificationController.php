<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\Sale;
use App\Services\Documents\SaleVerificationToken;
use Illuminate\Contracts\View\View;

final readonly class SaleVerificationController
{
    public function __construct(
        private SaleVerificationToken $tokens,
    ) {}

    public function __invoke(
        Sale $sale,
        string $token,
    ): View {
        abort_unless(
            $this->tokens->valid($sale, $token),
            404,
        );

        return view('public.sale-verification', [
            'sale' => $sale->load([
                'branch:id,name,address',
                'customer:id,name',
                'items',
            ]),
        ]);
    }
}
