<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Api;

use App\Actions\Api\CreateApiToken;
use App\Http\Requests\Api\StoreApiTokenRequest;
use App\Models\Account;
use App\Models\ApiToken;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class ApiTokenController
{
    public function __construct(
        private CreateApiToken $create,
        private AuditLogger $audit,
    ) {}

    public function index(): View
    {
        return view('admin.api.tokens', [
            'tokens' => ApiToken::query()
                ->with('createdBy:id,first_name,last_name')
                ->orderByDesc('created_at')
                ->paginate(40),
            'abilities' => [
                '*' => 'Full API access',
                'products.read' => 'Read products',
                'customers.read' => 'Read customers',
                'sales.read' => 'Read sales and quotes',
                'sales.create' => 'Create sales and quotes',
                'quotes.convert' => 'Convert quotes',
                'reports.read' => 'Read reports',
            ],
        ]);
    }

    public function store(
        StoreApiTokenRequest $request,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();

        $created = $this->create->execute(
            $actor,
            $request->string('name')->trim()->toString(),
            $request->array('abilities'),
            $request->date('expires_at'),
        );

        $this->audit->record(
            $request,
            'api-token.created',
            'api_token',
            $created['token'],
            after: [
                'name' => $created['token']->name,
                'abilities' => $created['token']->abilities,
                'expires_at' => $created['token']->expires_at?->toIso8601String(),
            ],
        );

        return back()
            ->with('status', 'API token created.')
            ->with('new_api_token', $created['plaintext']);
    }

    public function destroy(
        Request $request,
        ApiToken $token,
    ): RedirectResponse {
        $token->forceFill(['revoked_at' => now()])->save();

        $this->audit->record(
            $request,
            'api-token.revoked',
            'api_token',
            $token,
            after: ['revoked_at' => now()->toIso8601String()],
        );

        return back()->with('status', 'API token revoked.');
    }
}
