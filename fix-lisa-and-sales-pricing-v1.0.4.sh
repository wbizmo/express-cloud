#!/usr/bin/env bash

set -euo pipefail

echo "==> Applying targeted Lisa AI and sales pricing corrections"

required_files=(
  "app/Http/Controllers/Admin/Sales/SaleController.php"
  "app/Support/Authorization/PermissionCatalog.php"
  "app/Support/Authorization/Sprint4Permissions.php"
  "database/seeders/Sprint4EnterprisePermissionSeeder.php"
  "config/navigation.php"
  "routes/admin.php"
)

for file in "${required_files[@]}"; do
  if [[ ! -f "$file" ]]; then
    echo "ERROR: Required file is missing: $file" >&2
    exit 1
  fi
done

###############################################################################
# 1. Make Sales/POS read branch prices from ProductBranchPrice.
###############################################################################

cat > /tmp/expresscloud_fix_sale_prices.pl <<'PERL'
use strict;
use warnings;

my $file = 'app/Http/Controllers/Admin/Sales/SaleController.php';

open my $in, '<', $file or die "Cannot read $file: $!";
local $/;
my $source = <$in>;
close $in;

if ($source !~ /use App\\Models\\ProductBranchPrice;/) {
    if ($source =~ /use App\\Models\\ProductBranchStock;\n/) {
        $source =~ s/use App\\Models\\ProductBranchStock;\n/use App\\Models\\ProductBranchPrice;\nuse App\\Models\\ProductBranchStock;\n/;
    } else {
        die "Could not locate ProductBranchStock import in $file\n";
    }
}

my $replacement = <<'PHP';
'productPrices' => ProductBranchPrice::query()
                ->get(['product_id', 'branch_id', 'price_kobo'])
                ->mapWithKeys(static fn (ProductBranchPrice $price): array => [
                    $price->branch_id.'|'.$price->product_id => (int) $price->price_kobo,
                ]),
PHP

my $changed = 0;

$changed += ($source =~ s{
    'productPrices'\s*=>\s*ProductBranchStock::query\(\)
    \s*->whereNotNull\('selling_price_kobo'\)
    \s*->get\(\['product_id',\s*'branch_id',\s*'selling_price_kobo'\]\)
    \s*->mapWithKeys\(static\s+fn\s+\(ProductBranchStock\s+\$stock\):\s*array\s*=>\s*\[
    \s*\$stock->branch_id\.'\|'\.\$stock->product_id\s*=>\s*\(int\)\s*\$stock->selling_price_kobo,
    \s*\]\),
}{$replacement}sx);

if (!$changed && $source !~ /'productPrices'\s*=>\s*ProductBranchPrice::query\(\)/) {
    die "Could not safely replace the existing productPrices query in $file\n";
}

open my $out, '>', $file or die "Cannot write $file: $!";
print {$out} $source;
close $out;
PERL

perl /tmp/expresscloud_fix_sale_prices.pl
rm -f /tmp/expresscloud_fix_sale_prices.pl

###############################################################################
# 2. Replace the Sprint 4 permission seeder with a schema-correct implementation.
#
# The permissions table uses:
#   name, slug, group, description
#
# This explicitly merges PermissionCatalog and Sprint4Permissions so Lisa AI,
# bulk price adjustment and the rest of Sprint 4 are all created and granted.
###############################################################################

cat > database/seeders/Sprint4EnterprisePermissionSeeder.php <<'PHP'
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Authorization\PermissionCatalog;
use App\Support\Authorization\Sprint4Permissions;
use Illuminate\Database\Seeder;

final class Sprint4EnterprisePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $groupedPermissions = PermissionCatalog::grouped();

        foreach (Sprint4Permissions::grouped() as $group => $slugs) {
            foreach ($slugs as $slug) {
                $groupedPermissions[$group][$slug] ??= str($slug)
                    ->replace('.', ' ')
                    ->title()
                    ->toString();
            }
        }

        foreach ($groupedPermissions as $group => $permissions) {
            foreach ($permissions as $slug => $description) {
                Permission::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => str($slug)
                            ->replace('.', ' ')
                            ->title()
                            ->toString(),
                        'group' => (string) $group,
                        'description' => $description,
                    ],
                );
            }
        }

        $grant = static function (string $roleName, array $slugs): void {
            $role = Role::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($roleName)])
                ->first();

            if ($role === null) {
                return;
            }

            $permissionIds = Permission::query()
                ->whereIn('slug', array_values(array_unique($slugs)))
                ->pluck('id')
                ->all();

            $role->permissions()->syncWithoutDetaching($permissionIds);
        };

        $allPermissions = array_values(array_unique([
            ...PermissionCatalog::all(),
            ...Sprint4Permissions::all(),
        ]));

        foreach ([
            'System Owner',
            'Super Admin',
            'Admin',
            'Company Owner',
        ] as $roleName) {
            $grant($roleName, $allPermissions);
        }

        $grant(
            'Branch Manager',
            array_values(array_diff(
                $allPermissions,
                [
                    'activity.view.all-branches',
                    'lisa.audit.view',
                ],
            )),
        );

        $inventoryPermissions = [
            'inventory.view',
            'inventory.transfer',
            'inventory.intake',
            'inventory.adjust',
            'products.view',
            'products.prices.adjust',
            'suppliers.view',
            'procurement.view',
            'procurement.create',
            'procurement.receive',
            'purchases.view',
            'purchases.create',
            'categories.manage',
            'brands.manage',
            'branches.view',
        ];

        $inventoryRole = Role::query()->firstOrCreate(
            ['name' => 'Inventory Staff'],
            [
                'description' => 'Branch-scoped inventory and purchasing access.',
            ],
        );

        $inventoryRole->permissions()->syncWithoutDetaching(
            Permission::query()
                ->whereIn('slug', $inventoryPermissions)
                ->pluck('id')
                ->all(),
        );

        $grant('Accounting', [
            'suppliers.view',
            'procurement.view',
            'purchases.view',
            'exports.procurement',
            'reports.daily-digest.view',
            'insights.view',
        ]);
    }
}
PHP

###############################################################################
# 3. Static verification. No database connection is used.
###############################################################################

echo "==> Verifying PHP syntax"

php -l app/Http/Controllers/Admin/Sales/SaleController.php >/dev/null
php -l database/seeders/Sprint4EnterprisePermissionSeeder.php >/dev/null
php -l app/Support/Authorization/Sprint4Permissions.php >/dev/null
php -l app/Support/Authorization/PermissionCatalog.php >/dev/null
php -l routes/admin.php >/dev/null

echo "==> Verifying Sales/POS pricing source"

grep -Fq "use App\\Models\\ProductBranchPrice;" \
  app/Http/Controllers/Admin/Sales/SaleController.php

grep -Fq "'productPrices' => ProductBranchPrice::query()" \
  app/Http/Controllers/Admin/Sales/SaleController.php

grep -Fq "get(['product_id', 'branch_id', 'price_kobo'])" \
  app/Http/Controllers/Admin/Sales/SaleController.php

if grep -A10 -F "'productPrices' =>" \
  app/Http/Controllers/Admin/Sales/SaleController.php |
  grep -Fq "selling_price_kobo"; then
  echo "ERROR: productPrices still reads selling_price_kobo from stock." >&2
  exit 1
fi

echo "==> Verifying payment methods remain wired into Sales/POS"

grep -Fq "PaymentMethod::query()" \
  app/Http/Controllers/Admin/Sales/SaleController.php

grep -Fq "'paymentMethods' =>" \
  app/Http/Controllers/Admin/Sales/SaleController.php

grep -Fq "where('is_active', true)" \
  app/Http/Controllers/Admin/Sales/SaleController.php

echo "==> Verifying Lisa AI route construction"

grep -Fq "Route::prefix('lisa')->name('insights.chat.')" routes/admin.php
grep -Fq "middleware('permission:lisa.chat')->name('index')" routes/admin.php

echo "==> Verifying Lisa AI navigation"

grep -Fq "'label' => 'Lisa AI'" config/navigation.php
grep -Fq "'route' => 'admin.insights.index'" config/navigation.php
grep -Fq "'label' => 'Chat with Lisa'" config/navigation.php
grep -Fq "'route' => 'admin.insights.chat.index'" config/navigation.php
grep -Fq "'permission' => 'lisa.chat'" config/navigation.php

echo "==> Verifying Lisa and bulk-price permission definitions"

grep -Fq "'lisa.chat'" app/Support/Authorization/Sprint4Permissions.php
grep -Fq "'lisa.audit.view'" app/Support/Authorization/Sprint4Permissions.php
grep -Fq "'products.prices.adjust'" app/Support/Authorization/PermissionCatalog.php

grep -Fq "PermissionCatalog::all()" \
  database/seeders/Sprint4EnterprisePermissionSeeder.php

grep -Fq "Sprint4Permissions::all()" \
  database/seeders/Sprint4EnterprisePermissionSeeder.php

grep -Fq "'slug' => \$slug" \
  database/seeders/Sprint4EnterprisePermissionSeeder.php

grep -Fq "'System Owner'" \
  database/seeders/Sprint4EnterprisePermissionSeeder.php

grep -Fq "'Company Owner'" \
  database/seeders/Sprint4EnterprisePermissionSeeder.php

echo "==> Confirming no database command was executed"
echo "Static verification passed."

###############################################################################
# 4. Commit every current workspace change and push the active branch.
###############################################################################

echo "==> Git status before commit"
git status --short

git add -A

if git diff --cached --quiet; then
  echo "No changes are available to commit."
else
  git commit \
    -m "fix(v1.0.4): complete Lisa permissions and POS branch pricing" \
    -m "Create and grant the full Lisa AI permission set using the current permissions schema, keep Lisa navigation and routes statically verified, and make Sales/POS consume canonical ProductBranchPrice records."
fi

branch="$(git branch --show-current)"

if [[ -z "$branch" ]]; then
  echo "ERROR: Could not determine the current Git branch." >&2
  exit 1
fi

git push origin "$branch"

echo
echo "==> Corrections verified and pushed successfully"
echo "Current branch: $branch"
echo "Current commit: $(git rev-parse --short HEAD)"
echo
echo "IMPORTANT POST-DEPLOY DATABASE STEP:"
echo 'php artisan db:seed --class=Database\\Seeders\\Sprint4EnterprisePermissionSeeder'
echo
echo "That seeder command must run against the deployed database before existing"
echo "roles can receive Lisa AI and bulk price adjustment permissions."
