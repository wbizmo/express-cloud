<?php

declare(strict_types=1);

namespace App\Queries\Authentication;

use App\Enums\Authentication\AccountStatus;
use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;

final class SearchActiveAccounts
{
    /**
     * @return Collection<int, Account>
     */
    public function execute(string $term): Collection
    {
        $normalized = trim($term);

        if (mb_strlen($normalized) < 2) {
            return new Collection;
        }

        return Account::query()
            ->select([
                'id',
                'public_id',
                'first_name',
                'last_name',
                'profile_picture_path',
            ])
            ->where('status', AccountStatus::Active->value)
            ->where(function ($query) use ($normalized): void {
                $query
                    ->where('first_name', 'like', $normalized.'%')
                    ->orWhere('last_name', 'like', $normalized.'%')
                    ->orWhereRaw(
                        "CONCAT(first_name, ' ', last_name) LIKE ?",
                        [$normalized.'%'],
                    );
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit((int) config(
                'authentication.access_key.search_result_limit',
                20,
            ))
            ->get();
    }
}
