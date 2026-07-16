<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Authentication\AccountStatus;
use App\Enums\Authentication\SecurityEventType;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Role;
use App\Services\Authentication\SecurityEventRecorder;
use App\Services\Organisation\AuditLogger;
use App\Support\Security\BlindIndex;
use App\Support\Security\EncryptedValue;
use App\Support\Security\LoginKeyGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class StaffController
{
    public function __construct(
        private AuditLogger $audit,
        private SecurityEventRecorder $securityEvents,
        private LoginKeyGenerator $keyGenerator,
        private BlindIndex $blindIndex,
        private EncryptedValue $encryptedValue,
    ) {}

    public function index(): View
    {
        return view('admin.staff.index', [
            'accounts' => Account::query()
                ->with(['roles:id,name', 'branches:id,name'])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(25),
            'roles' => Role::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'branches' => Branch::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(
        StoreStaffRequest $request,
    ): RedirectResponse {
        $plainKey = $this->keyGenerator->generate();
        $normalizedKey = LoginKeyGenerator::normalize($plainKey);

        $account = DB::transaction(function () use (
            $request,
            $plainKey,
            $normalizedKey,
        ): Account {
            $account = Account::query()->create([
                'public_id' => (string) Str::uuid(),
                'first_name' => $request->string('first_name')
                    ->trim()
                    ->toString(),
                'last_name' => $request->string('last_name')
                    ->trim()
                    ->toString(),
                'email_encrypted' => $request->filled('email')
                    ? $this->encryptedValue->encrypt(
                        $request->string('email')->trim()->toString(),
                    )
                    : null,
                'login_key_encrypted' => $this->encryptedValue->encrypt(
                    $plainKey,
                ),
                'login_key_blind_index' => $this->blindIndex->make(
                    $normalizedKey,
                ),
                'login_key_version' => (int) config(
                    'express-cloud.security.data_encryption_version',
                    1,
                ),
                'status' => AccountStatus::Active,
                'is_allowed_all_branches' => $request->boolean(
                    'is_allowed_all_branches',
                ),
            ]);

            $account->roles()->sync($request->array('role_ids'));

            if (! $account->is_allowed_all_branches) {
                $account->branches()->sync(
                    $request->array('branch_ids'),
                );
            }

            return $account;
        });

        $this->audit->record(
            $request,
            'staff.created',
            'account',
            $account,
            after: [
                'name' => $account->displayName(),
                'status' => $account->status instanceof AccountStatus
                    ? $account->status->value
                    : (string) $account->status,
                'all_branches' => $account->is_allowed_all_branches,
            ],
        );

        return back()
            ->with('status', 'Staff account created.')
            ->with('generated_access_key', $plainKey);
    }

    public function suspend(
        Request $request,
        Account $account,
    ): RedirectResponse {
        $before = $account->toArray();

        DB::transaction(function () use ($account): void {
            $account->forceFill([
                'status' => AccountStatus::Suspended,
            ])->save();

            $account->getConnection()
                ->table('account_sessions')
                ->where('account_id', $account->getKey())
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
        });

        $this->audit->record(
            $request,
            'staff.suspended',
            'account',
            $account,
            before: $before,
            after: $account->fresh()?->toArray(),
        );

        /** @var Account|null $actor */
        $actor = $request->user();

        $this->securityEvents->record(
            SecurityEventType::AccountSuspended,
            $request,
            actor: $actor,
            subject: $account,
        );

        return back()->with('status', 'Staff account suspended.');
    }
}
