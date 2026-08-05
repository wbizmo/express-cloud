<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BankReconciliationMatch;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\JournalLine;
use App\Models\OperationRequest;
use App\Services\Operations\CommandBoundary;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class BankReconciliationService
{
    public function __construct(private CommandBoundary $commands) {}

    /**
     * @param list<array{
     *   transaction_date:string,
     *   reference?:string|null,
     *   description:string,
     *   debit_kobo?:int,
     *   credit_kobo?:int,
     *   running_balance_kobo:int
     * }> $lines
     */
    public function import(
        BankAccount $bankAccount,
        Account $actor,
        string $idempotencyKey,
        string $startsOn,
        string $endsOn,
        int $openingBalanceKobo,
        int $closingBalanceKobo,
        array $lines,
    ): BankStatementImport {
        $payload = [
            'bank_account_id' => (string) $bankAccount->getKey(),
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'opening_balance_kobo' => $openingBalanceKobo,
            'closing_balance_kobo' => $closingBalanceKobo,
            'lines' => $lines,
        ];
        $result = $this->commands->execute(
            'accounting.bank-statement.import',
            $idempotencyKey,
            $payload,
            $actor,
            $bankAccount->branch_id,
            function (OperationRequest $operation) use (
                $bankAccount,
                $actor,
                $startsOn,
                $endsOn,
                $openingBalanceKobo,
                $closingBalanceKobo,
                $lines,
                $payload,
            ): BankStatementImport {
                $import = BankStatementImport::query()->create([
                    'bank_account_id' => $bankAccount->getKey(),
                    'imported_by_account_id' => $actor->getKey(),
                    'operation_request_id' => $operation->getKey(),
                    'starts_on' => $startsOn,
                    'ends_on' => $endsOn,
                    'opening_balance_kobo' => $openingBalanceKobo,
                    'closing_balance_kobo' => $closingBalanceKobo,
                    'file_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
                    'status' => 'open',
                    'imported_at' => now(),
                ]);

                foreach ($lines as $line) {
                    $debit = max(0, (int) ($line['debit_kobo'] ?? 0));
                    $credit = max(0, (int) ($line['credit_kobo'] ?? 0));
                    if (($debit > 0) === ($credit > 0)) {
                        throw new \DomainException(
                            'Each bank statement line must contain one debit or one credit.',
                        );
                    }
                    BankStatementLine::query()->create([
                        'bank_statement_import_id' => $import->getKey(),
                        'transaction_date' => CarbonImmutable::parse($line['transaction_date'])->toDateString(),
                        'reference' => isset($line['reference'])
                            ? trim((string) $line['reference'])
                            : null,
                        'description' => trim((string) $line['description']),
                        'debit_kobo' => $debit,
                        'credit_kobo' => $credit,
                        'running_balance_kobo' => (int) $line['running_balance_kobo'],
                        'status' => 'unmatched',
                    ]);
                }

                return $import;
            },
        );

        if (! $result instanceof BankStatementImport) {
            throw new \LogicException('The bank import command returned an invalid result.');
        }

        return $result;
    }

    public function match(
        BankStatementLine $statementLine,
        JournalLine $journalLine,
        Account $actor,
        ?int $amountKobo = null,
    ): BankReconciliationMatch {
        return DB::transaction(function () use (
            $statementLine,
            $journalLine,
            $actor,
            $amountKobo,
        ): BankReconciliationMatch {
            /** @var BankStatementLine $lockedStatement */
            $lockedStatement = BankStatementLine::query()
                ->whereKey($statementLine->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            /** @var JournalLine $lockedJournal */
            $lockedJournal = JournalLine::query()
                ->whereKey($journalLine->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            /** @var BankStatementImport $import */
            $import = BankStatementImport::query()
                ->whereKey($lockedStatement->bank_statement_import_id)
                ->lockForUpdate()
                ->firstOrFail();
            /** @var BankAccount $bankAccount */
            $bankAccount = BankAccount::query()
                ->whereKey($import->bank_account_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((string) $lockedJournal->ledger_account_id !== (string) $bankAccount->ledger_account_id) {
                throw new \DomainException(
                    'Only a journal line posted to the selected bank ledger account can be matched.',
                );
            }
            if (
                ($lockedStatement->debit_kobo > 0 && $lockedJournal->credit_kobo <= 0)
                || ($lockedStatement->credit_kobo > 0 && $lockedJournal->debit_kobo <= 0)
            ) {
                throw new \DomainException(
                    'The bank statement direction does not match the journal line direction.',
                );
            }

            $statementAmount = $lockedStatement->amountKobo();
            $journalAmount = max($lockedJournal->debit_kobo, $lockedJournal->credit_kobo);
            $alreadyStatement = (int) BankReconciliationMatch::query()
                ->where('bank_statement_line_id', $lockedStatement->getKey())
                ->sum('matched_amount_kobo');
            $alreadyJournal = (int) BankReconciliationMatch::query()
                ->where('journal_line_id', $lockedJournal->getKey())
                ->sum('matched_amount_kobo');
            $available = min(
                $statementAmount - $alreadyStatement,
                $journalAmount - $alreadyJournal,
            );
            $matchAmount = $amountKobo ?? $available;

            if ($matchAmount <= 0 || $matchAmount > $available) {
                throw new \DomainException('The reconciliation amount exceeds an unmatched balance.');
            }

            $match = BankReconciliationMatch::query()->create([
                'bank_statement_line_id' => $lockedStatement->getKey(),
                'journal_line_id' => $lockedJournal->getKey(),
                'matched_by_account_id' => $actor->getKey(),
                'matched_amount_kobo' => $matchAmount,
                'matched_at' => now(),
            ]);
            $matchedTotal = $alreadyStatement + $matchAmount;
            $lockedStatement->forceFill([
                'status' => $matchedTotal >= $statementAmount ? 'matched' : 'partially_matched',
            ])->save();

            $openLines = BankStatementLine::query()
                ->where('bank_statement_import_id', $import->getKey())
                ->whereNotIn('status', ['matched', 'ignored'])
                ->count();
            if ($openLines === 0) {
                $import->forceFill(['status' => 'matched'])->save();
            }

            return $match;
        }, 3);
    }

    public function finalize(BankStatementImport $statement): BankStatementImport
    {
        return DB::transaction(function () use ($statement): BankStatementImport {
            /** @var BankStatementImport $locked */
            $locked = BankStatementImport::query()
                ->whereKey($statement->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $openLines = BankStatementLine::query()
                ->where('bank_statement_import_id', $locked->getKey())
                ->whereNotIn('status', ['matched', 'ignored'])
                ->count();

            if ($openLines !== 0) {
                throw new \DomainException(
                    'Every bank statement line must be matched or explicitly ignored before finalization.',
                );
            }

            $debits = (int) BankStatementLine::query()
                ->where('bank_statement_import_id', $locked->getKey())
                ->sum('debit_kobo');
            $credits = (int) BankStatementLine::query()
                ->where('bank_statement_import_id', $locked->getKey())
                ->sum('credit_kobo');
            $expectedClosing = $locked->opening_balance_kobo + $credits - $debits;

            if ($expectedClosing !== $locked->closing_balance_kobo) {
                throw new \DomainException(
                    'The bank statement opening balance and transaction lines do not reconcile to the closing balance.',
                );
            }

            $locked->forceFill([
                'status' => 'reconciled',
                'reconciled_at' => now(),
            ])->save();

            return $locked;
        }, 3);
    }
}
