<?php

declare(strict_types=1);

namespace App\Queries\Authentication;

use App\Enums\Authentication\AccountStatus;
use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class SearchActiveAccounts
{
    /**
     * @return Collection<int, Account>
     */
    public function execute(string $term): Collection
    {
        $normalized = preg_replace('/\s+/u', ' ', mb_strtolower(trim($term)));

        if (! is_string($normalized) || mb_strlen($normalized) < 2) {
            return new Collection;
        }

        $parts = preg_split(
            '/\s+/u',
            $normalized,
            -1,
            PREG_SPLIT_NO_EMPTY,
        ) ?: [];

        return Account::query()
            ->select([
                'id',
                'public_id',
                'first_name',
                'last_name',
                'profile_picture_path',
            ])
            ->where('status', AccountStatus::Active->value)
            ->where(function (Builder $query) use (
                $normalized,
                $parts,
            ): void {
                $query
                    ->whereRaw(
                        'LOWER(first_name) LIKE ?',
                        [$normalized.'%'],
                    )
                    ->orWhereRaw(
                        'LOWER(last_name) LIKE ?',
                        [$normalized.'%'],
                    );

                if (count($parts) < 2) {
                    return;
                }

                $first = array_shift($parts);
                $last = implode(' ', $parts);

                $query
                    ->orWhere(
                        static function (Builder $nameQuery) use (
                            $first,
                            $last,
                        ): void {
                            $nameQuery
                                ->whereRaw(
                                    'LOWER(first_name) LIKE ?',
                                    [$first.'%'],
                                )
                                ->whereRaw(
                                    'LOWER(last_name) LIKE ?',
                                    [$last.'%'],
                                );
                        },
                    )
                    ->orWhere(
                        static function (Builder $nameQuery) use (
                            $first,
                            $last,
                        ): void {
                            $nameQuery
                                ->whereRaw(
                                    'LOWER(last_name) LIKE ?',
                                    [$first.'%'],
                                )
                                ->whereRaw(
                                    'LOWER(first_name) LIKE ?',
                                    [$last.'%'],
                                );
                        },
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
