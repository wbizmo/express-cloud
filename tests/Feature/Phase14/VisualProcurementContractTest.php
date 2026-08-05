<?php

declare(strict_types=1);

namespace Tests\Feature\Phase14;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class VisualProcurementContractTest extends TestCase
{
    #[Test]
    public function purchase_order_routes_expose_edit_cancel_and_outstanding_close(): void
    {
        $routes = file_get_contents(base_path('routes/admin.php'));
        self::assertIsString($routes);
        self::assertStringContainsString('orders.edit', $routes);
        self::assertStringContainsString('orders.update', $routes);
        self::assertStringContainsString('orders.cancel', $routes);
        self::assertStringContainsString('orders.cancel-outstanding', $routes);
        self::assertStringContainsString('receipts.void', $routes);
        self::assertStringContainsString('landed-costs.reverse', $routes);
    }

    #[Test]
    public function legacy_purchase_order_controller_does_not_double_post_stock(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Admin/Procurement/PurchaseOrderController.php'));
        self::assertIsString($source);
        self::assertStringNotContainsString('updateStockForPurchaseOrder', $source);
        self::assertStringNotContainsString('StockLedger', $source);
        self::assertSame(1, substr_count($source, '$receiver->execute('));
    }

    #[Test]
    public function procurement_reversal_services_preserve_compensating_workflows(): void
    {
        $source = file_get_contents(app_path('Services/Procurement/ProcurementReversalService.php'));
        $ledger = file_get_contents(app_path('Services/Inventory/WarehouseStockLedger.php'));
        self::assertIsString($source);
        self::assertIsString($ledger);
        self::assertStringContainsString('voidReceipt(', $source);
        self::assertStringContainsString('reverseLandedCost(', $source);
        self::assertStringContainsString('reverseReceipt(', $ledger);
        self::assertStringContainsString('reverseCapitalizedCost(', $ledger);
        self::assertStringNotContainsString('->delete()', $source);
    }

    #[Test]
    public function material_symbols_replace_lucide_runtime_rendering(): void
    {
        $icon = file_get_contents(resource_path('views/components/ui/icon.blade.php'));
        $layout = file_get_contents(resource_path('views/components/layout/app.blade.php'));
        $javascript = file_get_contents(resource_path('js/app.js'));
        self::assertIsString($icon);
        self::assertIsString($layout);
        self::assertIsString($javascript);
        self::assertStringContainsString('material-symbols-outlined', $icon);
        self::assertStringContainsString('Material+Symbols+Outlined', $layout);
        self::assertStringNotContainsString('data-lucide', $icon);
        self::assertStringNotContainsString("from 'lucide'", $javascript);
        self::assertStringContainsString('fonts.googleapis.com', (string) config('security.content_security_policy'));
        self::assertStringContainsString('fonts.gstatic.com', (string) config('security.content_security_policy'));
    }

    #[Test]
    public function user_interface_sources_contain_no_emoji_characters(): void
    {
        $files = [];
        foreach ([resource_path('views'), resource_path('js')] as $root) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                $files[] = $file->getPathname();
            }
        }

        $violations = [];
        foreach ($files as $path) {
            $source = file_get_contents($path);
            if (! is_string($source)) {
                continue;
            }
            if (preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $source) === 1) {
                $violations[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
            }
        }

        self::assertSame([], $violations, 'Emoji characters found in UI sources: '.implode(', ', $violations));
    }
}
