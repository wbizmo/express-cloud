<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Queries\Authentication\SearchActiveAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class AccountSearchController
{
    public function __construct(
        private SearchActiveAccounts $search,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $term = $request->string('q')->trim()->toString();

        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $results = $this->search->execute($term)
            ->map(static fn ($account): array => [
                'id' => $account->public_id,
                'name' => $account->displayName(),
                'initials' => $account->initials(),
                'profile_picture_path' => $account->profile_picture_path,
            ])
            ->values();

        return response()->json(['data' => $results]);
    }
}
