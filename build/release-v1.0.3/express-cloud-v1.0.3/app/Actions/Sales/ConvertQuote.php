<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Enums\Sales\SaleType;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Models\Account;
use App\Models\Sale;

final readonly class ConvertQuote
{
    public function __construct(private CreateSale $createSale) {}

    public function execute(
        Sale $quote,
        StoreSaleRequest $request,
        Account $actor,
    ): Sale {
        $quoteType = $quote->sale_type instanceof SaleType
            ? $quote->sale_type
            : SaleType::from((string) $quote->sale_type);

        if ($quoteType !== SaleType::Quote) {
            throw new \DomainException(
                'Only quotes may be converted.',
            );
        }

        $sale = $this->createSale->execute($request, $actor);

        $sale->forceFill([
            'converted_from_sale_id' => $quote->getKey(),
        ])->save();

        return $sale;
    }
}
