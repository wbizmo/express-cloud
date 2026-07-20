<?php

declare(strict_types=1);

namespace App\Queries\Accounting;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class FinancialReports
{
    /** @return Collection<int, \stdClass> */
    public function trialBalance(string $from, string $to): Collection
    {
        return DB::table('journal_lines')
            ->join(
                'journal_entries',
                'journal_entries.id',
                '=',
                'journal_lines.journal_entry_id',
            )
            ->join(
                'ledger_accounts',
                'ledger_accounts.id',
                '=',
                'journal_lines.ledger_account_id',
            )
            ->where('journal_entries.status', 'posted')
            ->whereBetween('journal_entries.entry_date', [$from, $to])
            ->groupBy(
                'ledger_accounts.id',
                'ledger_accounts.code',
                'ledger_accounts.name',
                'ledger_accounts.type',
            )
            ->orderBy('ledger_accounts.code')
            ->select([
                'ledger_accounts.code',
                'ledger_accounts.name',
                'ledger_accounts.type',
            ])
            ->selectRaw('SUM(journal_lines.debit_kobo) AS debit_kobo')
            ->selectRaw('SUM(journal_lines.credit_kobo) AS credit_kobo')
            ->get();
    }

    /** @return Collection<int, \stdClass> */
    public function generalLedger(
        string $accountId,
        string $from,
        string $to,
    ): Collection {
        return DB::table('journal_lines')
            ->join(
                'journal_entries',
                'journal_entries.id',
                '=',
                'journal_lines.journal_entry_id',
            )
            ->where('journal_lines.ledger_account_id', $accountId)
            ->where('journal_entries.status', 'posted')
            ->whereBetween('journal_entries.entry_date', [$from, $to])
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.journal_number')
            ->get([
                'journal_entries.journal_number',
                'journal_entries.entry_date',
                'journal_entries.memo',
                'journal_lines.debit_kobo',
                'journal_lines.credit_kobo',
                'journal_lines.description',
            ]);
    }

    /**
     * Income Statement (Profit & Loss) for a date range: revenue and
     * expense accounts only, netted to a profit/loss figure.
     *
     * @return array{
     *     revenue: Collection<int, \stdClass>,
     *     expense: Collection<int, \stdClass>,
     *     total_revenue_kobo: int,
     *     total_expense_kobo: int,
     *     net_profit_kobo: int,
     * }
     */
    public function incomeStatement(string $from, string $to): array
    {
        $lines = $this->accountMovements($from, $to, ['revenue', 'expense']);

        $revenue = $lines->where('type', 'revenue')->values();
        $expense = $lines->where('type', 'expense')->values();

        // Revenue accounts carry a natural credit balance, expense a
        // natural debit balance — net each one the "right way round" so
        // a bigger number always means "more revenue" / "more expense".
        $totalRevenue = (int) $revenue->sum(
            static fn ($row) => $row->credit_kobo - $row->debit_kobo,
        );
        $totalExpense = (int) $expense->sum(
            static fn ($row) => $row->debit_kobo - $row->credit_kobo,
        );

        return [
            'revenue' => $revenue,
            'expense' => $expense,
            'total_revenue_kobo' => $totalRevenue,
            'total_expense_kobo' => $totalExpense,
            'net_profit_kobo' => $totalRevenue - $totalExpense,
        ];
    }

    /**
     * Balance Sheet as of a given date. Since this system does not run a
     * formal period-close, retained earnings is shown as the running net
     * income for all posted activity up to (and including) $asOf, exactly
     * how most small-business accounting tools present an "unclosed"
     * books balance sheet.
     *
     * @return array{
     *     assets: Collection<int, \stdClass>,
     *     liabilities: Collection<int, \stdClass>,
     *     equity: Collection<int, \stdClass>,
     *     total_assets_kobo: int,
     *     total_liabilities_kobo: int,
     *     total_equity_kobo: int,
     *     retained_earnings_kobo: int,
     * }
     */
    public function balanceSheet(string $asOf): array
    {
        $inception = '1970-01-01';
        $lines = $this->accountMovements(
            $inception,
            $asOf,
            ['asset', 'liability', 'equity', 'revenue', 'expense'],
        );

        $assets = $lines->where('type', 'asset')->values();
        $liabilities = $lines->where('type', 'liability')->values();
        $equity = $lines->where('type', 'equity')->values();

        $totalAssets = (int) $assets->sum(
            static fn ($row) => $row->debit_kobo - $row->credit_kobo,
        );
        $totalLiabilities = (int) $liabilities->sum(
            static fn ($row) => $row->credit_kobo - $row->debit_kobo,
        );
        $totalEquity = (int) $equity->sum(
            static fn ($row) => $row->credit_kobo - $row->debit_kobo,
        );

        $retainedEarnings = (int) $lines->where('type', 'revenue')->sum(
            static fn ($row) => $row->credit_kobo - $row->debit_kobo,
        ) - (int) $lines->where('type', 'expense')->sum(
            static fn ($row) => $row->debit_kobo - $row->credit_kobo,
        );

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets_kobo' => $totalAssets,
            'total_liabilities_kobo' => $totalLiabilities,
            'total_equity_kobo' => $totalEquity + $retainedEarnings,
            'retained_earnings_kobo' => $retainedEarnings,
        ];
    }

    /**
     * A simple cash-flow summary: net movement through cash/bank ledger
     * accounts for the period, grouped by the originating event type
     * (sale, purchase, payment, etc) so the reader can see *why* cash
     * moved rather than only the net figure.
     *
     * @return array{
     *     opening_kobo: int,
     *     closing_kobo: int,
     *     net_movement_kobo: int,
     *     by_source: Collection<int, \stdClass>,
     * }
     */
    public function cashFlowSummary(string $from, string $to): array
    {
        $cashAccountIds = DB::table('ledger_accounts')
            ->whereIn('code', ['1000', '1010', '1020'])
            ->pluck('id');

        $openingKobo = (int) DB::table('journal_lines')
            ->join(
                'journal_entries',
                'journal_entries.id',
                '=',
                'journal_lines.journal_entry_id',
            )
            ->whereIn('journal_lines.ledger_account_id', $cashAccountIds)
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.entry_date', '<', $from)
            ->selectRaw(
                'COALESCE(SUM(journal_lines.debit_kobo - journal_lines.credit_kobo), 0) AS total',
            )
            ->value('total');

        $bySource = DB::table('journal_lines')
            ->join(
                'journal_entries',
                'journal_entries.id',
                '=',
                'journal_lines.journal_entry_id',
            )
            ->whereIn('journal_lines.ledger_account_id', $cashAccountIds)
            ->where('journal_entries.status', 'posted')
            ->whereBetween('journal_entries.entry_date', [$from, $to])
            ->groupBy('journal_entries.source_type')
            ->orderByDesc(
                DB::raw('SUM(journal_lines.debit_kobo - journal_lines.credit_kobo)'),
            )
            ->select('journal_entries.source_type')
            ->selectRaw(
                'COALESCE(SUM(journal_lines.debit_kobo - journal_lines.credit_kobo), 0) AS net_kobo',
            )
            ->get();

        $netMovement = (int) $bySource->sum('net_kobo');

        return [
            'opening_kobo' => $openingKobo,
            'closing_kobo' => $openingKobo + $netMovement,
            'net_movement_kobo' => $netMovement,
            'by_source' => $bySource,
        ];
    }

    /**
     * Shared building block: net debit/credit movement per ledger account
     * for a date range, restricted to the given account types.
     *
     * @param  list<string>  $types
     * @return Collection<int, \stdClass>
     */
    private function accountMovements(
        string $from,
        string $to,
        array $types,
    ): Collection {
        return DB::table('journal_lines')
            ->join(
                'journal_entries',
                'journal_entries.id',
                '=',
                'journal_lines.journal_entry_id',
            )
            ->join(
                'ledger_accounts',
                'ledger_accounts.id',
                '=',
                'journal_lines.ledger_account_id',
            )
            ->whereIn('ledger_accounts.type', $types)
            ->where('journal_entries.status', 'posted')
            ->whereBetween('journal_entries.entry_date', [$from, $to])
            ->groupBy(
                'ledger_accounts.id',
                'ledger_accounts.code',
                'ledger_accounts.name',
                'ledger_accounts.type',
            )
            ->orderBy('ledger_accounts.code')
            ->select([
                'ledger_accounts.code',
                'ledger_accounts.name',
                'ledger_accounts.type',
            ])
            ->selectRaw('SUM(journal_lines.debit_kobo) AS debit_kobo')
            ->selectRaw('SUM(journal_lines.credit_kobo) AS credit_kobo')
            ->get();
    }
}
