<?php

declare(strict_types=1);

namespace App\Services\Authentication;

use App\Enums\Authentication\AccountStatus;
use App\Models\Account;
use App\Support\Security\BlindIndex;
use App\Support\Security\LoginKeyGenerator;

final readonly class AccessKeyAuthenticator
{
    public function __construct(private BlindIndex $blindIndex) {}

    public function findAuthenticatableAccount(
        string $accountPublicId,
        string $submittedKey,
    ): ?Account {
        $normalizedKey = LoginKeyGenerator::normalize($submittedKey);
        $submittedIndex = $this->blindIndex->make($normalizedKey);

        $account = Account::query()
            ->where('public_id', $accountPublicId)
            ->where('status', AccountStatus::Active->value)
            ->first();

        if (! $account instanceof Account) {
            return null;
        }

        if (! hash_equals($account->login_key_blind_index, $submittedIndex)) {
            return null;
        }

        return $account;
    }
}
