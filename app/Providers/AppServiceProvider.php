<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Account;
use App\Models\AdminNotification;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\BusinessInsight;
use App\Models\FixedAsset;
use App\Models\JournalEntry;
use App\Models\LisaConversation;
use App\Models\LowStockAlert;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StandaloneReceipt;
use App\Models\StockMovement;
use App\Models\SupplierBill;
use App\Models\SupplierBillPayment;
use App\Models\SupplierReturn;
use App\Observers\FinancialSourceObserver;
use App\Policies\BranchScopedResourcePolicy;
use App\Services\Organisation\AuthorizationService;
use App\Services\Organisation\BranchAccess;
use App\Services\Organisation\NavigationVisibility;
use App\Support\Authorization\PermissionCatalog;
use App\Support\Authorization\RolePermissionPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(AuthorizationService::class);
        $this->app->scoped(BranchAccess::class);
        $this->app->scoped(NavigationVisibility::class);
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());
        Model::preventAccessingMissingAttributes(! app()->isProduction());

        foreach ([
            Payment::class,
            PurchaseReceipt::class,
            PurchaseReturn::class,
            SaleReturn::class,
            StandaloneReceipt::class,
            StockMovement::class,
            SupplierBill::class,
            SupplierBillPayment::class,
            SupplierReturn::class,
            FixedAsset::class,
        ] as $financialSource) {
            $financialSource::observe(FinancialSourceObserver::class);
        }

        $permissions = PermissionCatalog::all();

        foreach (RolePermissionPolicy::all() as $rolePermissions) {
            array_push($permissions, ...$rolePermissions);
        }

        foreach (array_values(array_unique($permissions)) as $permission) {
            Gate::define(
                $permission,
                static fn (Account $account): bool => app(AuthorizationService::class)
                    ->hasPermission($account, $permission),
            );
        }

        foreach ([
            AdminNotification::class,
            AuditLog::class,
            Branch::class,
            BusinessInsight::class,
            FixedAsset::class,
            JournalEntry::class,
            LisaConversation::class,
            LowStockAlert::class,
            PurchaseOrder::class,
            PurchaseReceipt::class,
            PurchaseReturn::class,
            Sale::class,
            SaleReturn::class,
            StandaloneReceipt::class,
            StockMovement::class,
            SupplierBill::class,
            SupplierReturn::class,
        ] as $model) {
            Gate::policy($model, BranchScopedResourcePolicy::class);
        }

        if ((bool) config('express-cloud.http.force_https')) {
            URL::forceScheme('https');
        }
    }
}
