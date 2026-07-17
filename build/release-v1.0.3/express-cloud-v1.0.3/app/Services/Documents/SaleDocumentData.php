<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\BusinessSetting;
use App\Models\Sale;

final readonly class SaleDocumentData
{
    public function __construct(
        private SaleVerificationToken $tokens,
        private EmbeddedQrCode $qr,
    ) {}

    /** @return array<string, mixed> */
    public function make(Sale $sale): array
    {
        $sale->loadMissing([
            'branch:id,name,address,phone',
            'customer:id,name,phone,address',
            'soldBy:id,first_name,last_name',
            'items',
            'payments.paymentMethod:id,name',
        ]);

        $token = $this->tokens->issue($sale);
        $verificationUrl = route(
            'public.sales.verify',
            [$sale, $token],
        );

        return [
            'sale' => $sale,
            'settings' => BusinessSetting::current(),
            'verificationUrl' => $verificationUrl,
            'qrDataUri' => $this->qr->dataUri($verificationUrl),
        ];
    }
}
