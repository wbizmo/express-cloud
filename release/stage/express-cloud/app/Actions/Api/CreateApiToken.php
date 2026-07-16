<?php

declare(strict_types=1);

namespace App\Actions\Api;

use App\Models\Account;
use App\Models\ApiToken;
use App\Services\Api\ApiTokenGenerator;
use Illuminate\Support\Carbon;

final readonly class CreateApiToken
{
    public function __construct(private ApiTokenGenerator $generator) {}

    /**
     * @param  list<string>  $abilities
     * @return array{token:ApiToken, plaintext:string}
     */
    public function execute(
        Account $actor,
        string $name,
        array $abilities,
        ?Carbon $expiresAt,
    ): array {
        $generated = $this->generator->generate();

        $token = ApiToken::query()->create([
            'name' => $name,
            'token_prefix' => $generated['prefix'],
            'token_hash' => $generated['hash'],
            'abilities' => array_values(array_unique($abilities)),
            'created_by_account_id' => $actor->getKey(),
            'expires_at' => $expiresAt,
        ]);

        return [
            'token' => $token,
            'plaintext' => $generated['plaintext'],
        ];
    }
}
