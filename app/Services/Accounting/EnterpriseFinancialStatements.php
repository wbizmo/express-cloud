<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Models\LedgerAccount;
use App\Models\WarehouseStockBalance;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EnterpriseFinancialStatements
{
    /** @return Collection<int, \stdClass> */
    public function trialBalance(CarbonInterface $asOf, ?string $branchId = null): Collection
    {
        return $this->ledgerBalances($asOf, $branchId)
            ->map(function (object $row): object {
                $type = (string) $row->type;
                $debit = (int) $row->debit_kobo;
                $credit = (int) $row->credit_kobo;
                $normalDebit = in_array($type, ['asset', 'expense'], true);
                $balance = $normalDebit ? $debit - $credit : $credit - $debit;
                $row->balance_kobo = $balance;
                $row->normal_side = $normalDebit ? 'debit' : 'credit';

                return $row;
            })
            ->values();
    }

    /** @return array{revenue_kobo:int,expense_kobo:int,profit_kobo:int,accounts:Collection<int,\stdClass>} */
    public function profitAndLoss(
        CarbonInterface $from,
        CarbonInterface $to,
        ?string $branchId = null,
    ): array {
        $accounts = $this->periodBalances($from, $to, $branchId)
            ->whereIn('type', ['revenue', 'expense'])
            ->values();
        $revenue = (int) $accounts->where('type', 'revenue')
            ->sum(fn (object $row): int => (int) $row->credit_kobo - (int) $row->debit_kobo);
        $expense = (int) $accounts->where('type', 'expense')
            ->sum(fn (object $row): int => (int) $row->debit_kobo - (int) $row->credit_kobo);

        return [
            'revenue_kobo' => $revenue,
            'expense_kobo' => $expense,
            'profit_kobo' => $revenue - $expense,
            'accounts' => $accounts,
        ];
    }

    /** @return array{assets_kobo:int,liabilities_kobo:int,equity_kobo:int,difference_kobo:int,accounts:Collection<int,\stdClass>} */
    public function balanceSheet(CarbonInterface $asOf, ?string $branchId = null): array
    {
        $accounts = $this->trialBalance($asOf, $branchId)
            ->whereIn('type', ['asset', 'liability', 'equity'])
            ->values();
        $assets = (int) $accounts->where('type', 'asset')->sum('balance_kobo');
        $liabilities = (int) $accounts->where('type', 'liability')->sum('balance_kobo');
        $equity = (int) $accounts->where('type', 'equity')->sum('balance_kobo');

        return [
            'assets_kobo' => $assets,
            'liabilities_kobo' => $liabilities,
            'equity_kobo' => $equity,
            'difference_kobo' => $assets - $liabilities - $equity,
            'accounts' => $accounts,
        ];
    }

    /** @return array{operating_kobo:int,investing_kobo:int,financing_kobo:int,net_change_kobo:int,accounts:Collection<int,\stdClass>} */
    public function cashFlow(
        CarbonInterface $from,
        CarbonInterface $to,
        ?string $branchId = null,
    ): array {
        $accounts = $this->periodBalances($from, $to, $branchId)
            ->filter(static fn (object $row): bool => in_array((string) $row->cash_flow_section, ['operating', 'investing', 'financing'], true)
            )
            ->map(function (object $row): object {
                $row->cash_movement_kobo = (int) $row->debit_kobo - (int) $row->credit_kobo;

                return $row;
            })
            ->values();

        return [
            'operating_kobo' => (int) $accounts->where('cash_flow_section', 'operating')
                ->sum('cash_movement_kobo'),
            'investing_kobo' => (int) $accounts->where('cash_flow_section', 'investing')
                ->sum('cash_movement_kobo'),
            'financing_kobo' => (int) $accounts->where('cash_flow_section', 'financing')
                ->sum('cash_movement_kobo'),
            'net_change_kobo' => (int) $accounts->sum('cash_movement_kobo'),
            'accounts' => $accounts,
        ];
    }

    /** @return Collection<int, \stdClass> */
    public function cashBook(
        CarbonInterface $from,
        CarbonInterface $to,
        ?string $branchId = null,
    ): Collection {
        return $this->bookEntries($from, $to, ['cash'], $branchId);
    }

    /** @return Collection<int, \stdClass> */
    public function bankBook(
        CarbonInterface $from,
        CarbonInterface $to,
        ?string $branchId = null,
    ): Collection {
        return $this->bookEntries($from, $to, ['bank', 'clearing'], $branchId);
    }

    /** @return Collection<int, \stdClass> */
    public function taxLedger(
        CarbonInterface $from,
        CarbonInterface $to,
        ?string $branchId = null,
    ): Collection {
        return DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->where('journal_entries.status', 'posted')
            ->whereNotNull('ledger_accounts.tax_type')
            ->whereBetween('journal_entries.entry_date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->when(
                $branchId !== null,
                static fn (Builder $query): Builder => $query->where(
                    'journal_entries.branch_id',
                    $branchId,
                ),
            )
            ->select([
                'journal_entries.journal_number',
                'journal_entries.entry_date',
                'journal_entries.memo',
                'ledger_accounts.code',
                'ledger_accounts.name',
                'ledger_accounts.tax_type',
                'journal_lines.tax_basis_kobo',
                'journal_lines.tax_amount_kobo',
                'journal_lines.debit_kobo',
                'journal_lines.credit_kobo',
            ])
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.journal_number')
            ->get();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, WarehouseStockBalance> */
    public function inventoryValuation(?string $branchId = null): Collection
    {
        return WarehouseStockBalance::query()
            ->join('warehouses', 'warehouses.id', '=', 'warehouse_stock_balances.warehouse_id')
            ->join('products', 'products.id', '=', 'warehouse_stock_balances.product_id')
            ->when(
                $branchId !== null,
                static fn ($query) => $query->where('warehouses.branch_id', $branchId),
            )
            ->select([
                'warehouse_stock_balances.id',
                'warehouses.name as warehouse_name',
                'products.name as product_name',
                'products.sku',
                'warehouse_stock_balances.condition',
                'warehouse_stock_balances.quantity_milliunits',
                'warehouse_stock_balances.reserved_milliunits',
                'warehouse_stock_balances.weighted_average_cost_kobo',
                'warehouse_stock_balances.inventory_value_kobo',
            ])
            ->orderBy('warehouses.name')
            ->orderBy('products.name')
            ->get();
    }

    /** @return array{control_balance_kobo:int,subledger_balance_kobo:int,difference_kobo:int,entities:Collection<int,\stdClass>} */
    public function customerSubledger(CarbonInterface $asOf, ?string $branchId = null): array
    {
        return $this->subledger($asOf, 'customer_id', 'accounts_receivable', $branchId);
    }

    /** @return array{control_balance_kobo:int,subledger_balance_kobo:int,difference_kobo:int,entities:Collection<int,\stdClass>} */
    public function supplierSubledger(CarbonInterface $asOf, ?string $branchId = null): array
    {
        return $this->subledger($asOf, 'supplier_id', 'accounts_payable', $branchId);
    }

    /** @return array{accounts_receivable_difference_kobo:int,accounts_payable_difference_kobo:int,inventory_difference_kobo:int,total_difference_kobo:int} */
    public function controlReconciliation(CarbonInterface $asOf, ?string $branchId = null): array
    {
        $customers = $this->customerSubledger($asOf, $branchId);
        $suppliers = $this->supplierSubledger($asOf, $branchId);
        $inventoryCode = (string) config('accounting.codes.inventory');
        $inventoryLedger = $this->trialBalance($asOf, $branchId)
            ->firstWhere('code', $inventoryCode);
        $ledgerValue = is_object($inventoryLedger)
            ? (int) $inventoryLedger->balance_kobo
            : 0;

        $warehouseValue = (int) WarehouseStockBalance::query()
            ->when(
                $branchId !== null,
                static fn ($query) => $query->whereIn(
                    'warehouse_id',
                    DB::table('warehouses')->where('branch_id', $branchId)->select('id'),
                ),
            )
            ->sum('inventory_value_kobo');
        $inventoryDifference = $ledgerValue - $warehouseValue;

        return [
            'accounts_receivable_difference_kobo' => $customers['difference_kobo'],
            'accounts_payable_difference_kobo' => $suppliers['difference_kobo'],
            'inventory_difference_kobo' => $inventoryDifference,
            'total_difference_kobo' => abs($customers['difference_kobo'])
                + abs($suppliers['difference_kobo'])
                + abs($inventoryDifference),
        ];
    }

    /** @return Collection<int, \stdClass> */
    public function agedReceivables(CarbonInterface $asOf, ?string $branchId = null): Collection
    {
        return $this->agedSubledger($asOf, 'customer_id', 'accounts_receivable', $branchId);
    }

    /** @return Collection<int, \stdClass> */
    public function agedPayables(CarbonInterface $asOf, ?string $branchId = null): Collection
    {
        return $this->agedSubledger($asOf, 'supplier_id', 'accounts_payable', $branchId);
    }

    /** @return Collection<int, \stdClass> */
    private function ledgerBalances(CarbonInterface $asOf, ?string $branchId): Collection
    {
        return $this->baseLedgerQuery()
            ->whereDate('journal_entries.entry_date', '<=', $asOf->toDateString())
            ->when(
                $branchId !== null,
                static fn (Builder $query): Builder => $query->where('journal_entries.branch_id', $branchId),
            )
            ->groupBy([
                'ledger_accounts.id',
                'ledger_accounts.code',
                'ledger_accounts.name',
                'ledger_accounts.type',
                'ledger_accounts.group_code',
                'ledger_accounts.report_section',
                'ledger_accounts.cash_flow_section',
            ])
            ->orderBy('ledger_accounts.code')
            ->get();
    }

    /** @return Collection<int, \stdClass> */
    private function periodBalances(
        CarbonInterface $from,
        CarbonInterface $to,
        ?string $branchId,
    ): Collection {
        return $this->baseLedgerQuery()
            ->whereBetween('journal_entries.entry_date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->when(
                $branchId !== null,
                static fn (Builder $query): Builder => $query->where('journal_entries.branch_id', $branchId),
            )
            ->groupBy([
                'ledger_accounts.id',
                'ledger_accounts.code',
                'ledger_accounts.name',
                'ledger_accounts.type',
                'ledger_accounts.group_code',
                'ledger_accounts.report_section',
                'ledger_accounts.cash_flow_section',
            ])
            ->orderBy('ledger_accounts.code')
            ->get();
    }

    /**
     * @param  list<string>  $groups
     * @return Collection<int, \stdClass>
     */
    private function bookEntries(
        CarbonInterface $from,
        CarbonInterface $to,
        array $groups,
        ?string $branchId,
    ): Collection {
        return DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->where('journal_entries.status', 'posted')
            ->whereIn('ledger_accounts.group_code', $groups)
            ->whereBetween('journal_entries.entry_date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->when(
                $branchId !== null,
                static fn (Builder $query): Builder => $query->where(
                    'journal_entries.branch_id',
                    $branchId,
                ),
            )
            ->select([
                'journal_entries.journal_number',
                'journal_entries.entry_date',
                'journal_entries.memo',
                'journal_entries.book_type',
                'ledger_accounts.code',
                'ledger_accounts.name',
                'journal_lines.debit_kobo',
                'journal_lines.credit_kobo',
            ])
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.journal_number')
            ->get();
    }

    private function baseLedgerQuery(): Builder
    {
        return DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->where('journal_entries.status', 'posted')
            ->select([
                'ledger_accounts.id',
                'ledger_accounts.code',
                'ledger_accounts.name',
                'ledger_accounts.type',
                'ledger_accounts.group_code',
                'ledger_accounts.report_section',
                'ledger_accounts.cash_flow_section',
            ])
            ->selectRaw('SUM(journal_lines.debit_kobo) AS debit_kobo')
            ->selectRaw('SUM(journal_lines.credit_kobo) AS credit_kobo');
    }

    /** @return array{control_balance_kobo:int,subledger_balance_kobo:int,difference_kobo:int,entities:Collection<int,\stdClass>} */
    private function subledger(
        CarbonInterface $asOf,
        string $entityColumn,
        string $controlAccount,
        ?string $branchId,
    ): array {
        /** @var LedgerAccount $account */
        $account = LedgerAccount::query()
            ->where('code', config("accounting.codes.{$controlAccount}"))
            ->firstOrFail();
        $normalDebit = $controlAccount === 'accounts_receivable';

        $rows = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->whereDate('journal_entries.entry_date', '<=', $asOf->toDateString())
            ->where('journal_lines.ledger_account_id', $account->getKey())
            ->whereNotNull("journal_lines.{$entityColumn}")
            ->when(
                $branchId !== null,
                static fn (Builder $query): Builder => $query->where('journal_entries.branch_id', $branchId),
            )
            ->groupBy("journal_lines.{$entityColumn}")
            ->selectRaw("journal_lines.{$entityColumn} AS entity_id")
            ->selectRaw('SUM(journal_lines.debit_kobo) AS debit_kobo')
            ->selectRaw('SUM(journal_lines.credit_kobo) AS credit_kobo')
            ->get()
            ->map(function (object $row) use ($normalDebit): object {
                $row->balance_kobo = $normalDebit
                    ? (int) $row->debit_kobo - (int) $row->credit_kobo
                    : (int) $row->credit_kobo - (int) $row->debit_kobo;

                return $row;
            })
            ->filter(static fn (object $row): bool => (int) $row->balance_kobo !== 0)
            ->values();

        $control = $this->trialBalance($asOf, $branchId)
            ->firstWhere('code', $account->code);
        $controlBalance = is_object($control) ? (int) $control->balance_kobo : 0;
        $subledger = (int) $rows->sum('balance_kobo');

        return [
            'control_balance_kobo' => $controlBalance,
            'subledger_balance_kobo' => $subledger,
            'difference_kobo' => $controlBalance - $subledger,
            'entities' => $rows,
        ];
    }

    /** @return Collection<int, \stdClass> */
    private function agedSubledger(
        CarbonInterface $asOf,
        string $entityColumn,
        string $controlAccount,
        ?string $branchId,
    ): Collection {
        $accountCode = (string) config("accounting.codes.{$controlAccount}");
        $normalDebit = $controlAccount === 'accounts_receivable';

        return DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->where('ledger_accounts.code', $accountCode)
            ->where('journal_entries.status', 'posted')
            ->whereDate('journal_entries.entry_date', '<=', $asOf->toDateString())
            ->whereNotNull("journal_lines.{$entityColumn}")
            ->when(
                $branchId !== null,
                static fn (Builder $query): Builder => $query->where('journal_entries.branch_id', $branchId),
            )
            ->groupBy([
                "journal_lines.{$entityColumn}",
                'journal_entries.entry_date',
            ])
            ->selectRaw("journal_lines.{$entityColumn} AS entity_id")
            ->selectRaw('journal_entries.entry_date')
            ->selectRaw('SUM(journal_lines.debit_kobo) AS debit_kobo')
            ->selectRaw('SUM(journal_lines.credit_kobo) AS credit_kobo')
            ->get()
            ->map(function (object $row) use ($asOf, $normalDebit): object {
                $balance = $normalDebit
                    ? (int) $row->debit_kobo - (int) $row->credit_kobo
                    : (int) $row->credit_kobo - (int) $row->debit_kobo;
                $days = max(0, $asOf->diffInDays((string) $row->entry_date));
                $row->balance_kobo = $balance;
                $row->bucket = match (true) {
                    $days <= 30 => 'current',
                    $days <= 60 => '31_60',
                    $days <= 90 => '61_90',
                    default => 'over_90',
                };

                return $row;
            })
            ->filter(static fn (object $row): bool => (int) $row->balance_kobo !== 0)
            ->values();
    }
}
