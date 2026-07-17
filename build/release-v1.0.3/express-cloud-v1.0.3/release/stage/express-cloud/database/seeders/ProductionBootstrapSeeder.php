<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\Company;
use App\Models\DocumentBranding;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Authorization\PermissionCatalog;
use App\Support\Security\BlindIndex;
use App\Support\Security\EncryptedValue;
use App\Support\Security\LoginKeyGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ProductionBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $companyName = env('INSTALL_COMPANY_NAME');
        $branchName = env('INSTALL_BRANCH_NAME', 'Head Office');
        $adminFirstName = env('INSTALL_ADMIN_FIRST_NAME');
        $adminLastName = env('INSTALL_ADMIN_LAST_NAME');
        $adminEmail = env('INSTALL_ADMIN_EMAIL');
        $accessKey = env('INSTALL_ADMIN_KEY');

        foreach ([
            'INSTALL_COMPANY_NAME' => $companyName,
            'INSTALL_ADMIN_FIRST_NAME' => $adminFirstName,
            'INSTALL_ADMIN_LAST_NAME' => $adminLastName,
            'INSTALL_ADMIN_KEY' => $accessKey,
        ] as $name => $value) {
            if (! is_string($value) || trim($value) === '') {
                throw new \RuntimeException(
                    "{$name} must be set for production installation.",
                );
            }
        }

        $normalizedKey = LoginKeyGenerator::normalize($accessKey);

        DB::transaction(function () use (
            $companyName,
            $branchName,
            $adminFirstName,
            $adminLastName,
            $adminEmail,
            $normalizedKey,
        ): void {
            $encrypted = app(EncryptedValue::class);
            $blindIndex = app(BlindIndex::class);

            $company = Company::query()->firstOrCreate(
                ['legal_name' => $companyName],
                [
                    'trading_name' => $companyName,
                    'head_office_address' => env(
                        'INSTALL_COMPANY_ADDRESS',
                        'To be updated after installation',
                    ),
                    'phone' => env('INSTALL_COMPANY_PHONE'),
                    'email_encrypted' => is_string($adminEmail)
                        && $adminEmail !== ''
                            ? $encrypted->encrypt($adminEmail)
                            : null,
                    'timezone' => 'Africa/Lagos',
                    'is_configured' => true,
                ],
            );

            $branch = Branch::query()->firstOrCreate(
                ['code' => 'HQ'],
                [
                    'name' => $branchName,
                    'address' => env(
                        'INSTALL_COMPANY_ADDRESS',
                        'To be updated after installation',
                    ),
                    'phone' => env('INSTALL_COMPANY_PHONE'),
                    'status' => 'active',
                    'is_head_office' => true,
                ],
            );

            foreach (PermissionCatalog::grouped() as $group => $items) {
                foreach ($items as $slug => $name) {
                    Permission::query()->updateOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => $name,
                            'group' => $group,
                            'description' => $name,
                        ],
                    );
                }
            }

            $role = Role::query()->updateOrCreate(
                ['slug' => 'system-owner'],
                [
                    'name' => 'System Owner',
                    'description' => 'Full installation owner access.',
                    'is_system' => true,
                    'is_active' => true,
                ],
            );

            $role->permissions()->sync(
                Permission::query()->pluck('id')->all(),
            );

            $account = Account::query()->updateOrCreate(
                [
                    'login_key_blind_index' => $blindIndex->make(
                        $normalizedKey,
                    ),
                ],
                [
                    'public_id' => Str::uuid()->toString(),
                    'first_name' => $adminFirstName,
                    'last_name' => $adminLastName,
                    'email_encrypted' => is_string($adminEmail)
                        && $adminEmail !== ''
                            ? $encrypted->encrypt($adminEmail)
                            : null,
                    'login_key_encrypted' => $encrypted->encrypt(
                        $normalizedKey,
                    ),
                    'login_key_version' => 1,
                    'status' => 'active',
                    'is_allowed_all_branches' => true,
                ],
            );

            $account->roles()->syncWithoutDetaching([$role->getKey()]);
            $account->branches()->syncWithoutDetaching([$branch->getKey()]);

            foreach ([
                ['Cash', true],
                ['Bank Transfer', false],
                ['Card / POS Terminal', false],
                ['Customer Credit', false],
            ] as [$name, $default]) {
                PaymentMethod::query()->updateOrCreate(
                    ['name' => $name],
                    [
                        'description' => $name,
                        'is_system_default' => true,
                        'is_default_for_pos' => $default,
                        'is_active' => true,
                        'created_by_account_id' => $account->getKey(),
                    ],
                );
            }

            DocumentBranding::query()->firstOrCreate(
                [],
                [
                    'business_name' => $companyName,
                    'address' => $company->head_office_address,
                    'phone' => $company->phone,
                    'updated_by_account_id' => $account->getKey(),
                ],
            );

            AccountingPeriod::query()->firstOrCreate(
                [
                    'starts_on' => now()->startOfYear()->toDateString(),
                    'ends_on' => now()->endOfYear()->toDateString(),
                ],
                [
                    'name' => now()->format('Y').' Financial Year',
                    'status' => 'open',
                ],
            );
        });

        $this->call(ChartOfAccountsSeeder::class);
    }
}
