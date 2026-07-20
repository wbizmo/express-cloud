<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\Account;
use App\Models\BusinessSetting;
use App\Models\Sale;
use App\Services\Organisation\BranchAddressResolver;

final readonly class SaleDocumentData
{
    public function __construct(
        private SaleVerificationToken $tokens,
        private EmbeddedQrCode $qr,
        private BranchAddressResolver $branchAddress,
    ) {}

    /** @return array<string, mixed> */
    public function make(Sale $sale, ?Account $actor = null): array
    {
        $sale->loadMissing([
            'branch:id,name,address,phone,is_head_office',
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

        $documentBranch = $actor !== null
            ? $this->branchAddress->resolve($actor, $sale->branch)
            : $sale->branch;

        return [
            'sale' => $sale,
            'documentBranch' => $documentBranch,
            'settings' => BusinessSetting::current(),
            'verificationUrl' => $verificationUrl,
            'qrDataUri' => $this->qr->dataUri($verificationUrl),
        ];
    }
}
