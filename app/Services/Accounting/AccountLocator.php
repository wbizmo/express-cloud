<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Models\LedgerAccount;

final class AccountLocator
{
    public function byCode(string $code): LedgerAccount
    {
        return LedgerAccount::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function configured(string $name): LedgerAccount
    {
        $code = config("accounting.codes.{$name}");

        if (! is_string($code) || $code === '') {
            throw new \RuntimeException(
                "Accounting account mapping is missing: {$name}",
            );
        }

        return $this->byCode($code);
    }
}
