<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\CashCounter;
use App\Models\CashCounterMovement;
use App\Models\OperationRequest;
use App\Models\TreasuryAccount;
use App\Models\TreasuryMovement;
use App\Services\Operations\CommandBoundary;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final readonly class TreasuryService
{
    public function __construct(
        private CommandBoundary $commands,
        private JournalPoster $journals,
    ) {}

    public function transfer(
        TreasuryAccount $source,
        TreasuryAccount $destination,
        Account $actor,
        int $amountKobo,
        string $idempotencyKey,
        string $memo,
        ?string $reference = null,
        ?CashCounter $counter = null,
    ): TreasuryMovement {
        if ($source->is($destination) || $amountKobo <= 0) {
            throw new \InvalidArgumentException('A positive treasury transfer between different accounts is required.');
        }
        if ($source->currency !== $destination->currency) {
            throw new \DomainException('Treasury transfers require matching currencies.');
        }

        $payload = [
            'source' => (string) $source->getKey(),
            'destination' => (string) $destination->getKey(),
            'amount_kobo' => $amountKobo,
            'memo' => $memo,
            'reference' => $reference,
            'cash_counter_id' => $counter?->getKey(),
        ];
        $result = $this->commands->execute(
            'accounting.treasury.transfer',
            $idempotencyKey,
            $payload,
            $actor,
            $source->branch_id ?? $destination->branch_id,
            function (OperationRequest $operation) use (
                $source,
                $destination,
                $actor,
                $amountKobo,
                $memo,
                $reference,
                $counter,
            ): TreasuryMovement {
                /** @var TreasuryAccount $lockedSource */
                $lockedSource = TreasuryAccount::query()
                    ->whereKey($source->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                /** @var TreasuryAccount $lockedDestination */
                $lockedDestination = TreasuryAccount::query()
                    ->whereKey($destination->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                $journal = $this->journals->post(
                    CarbonImmutable::now(),
                    $memo,
                    [
                        [
                            'account_id' => (string) $lockedDestination->ledger_account_id,
                            'debit_kobo' => $amountKobo,
                        ],
                        [
                            'account_id' => (string) $lockedSource->ledger_account_id,
                            'credit_kobo' => $amountKobo,
                        ],
                    ],
                    $lockedSource->branch_id ?? $lockedDestination->branch_id,
                    (string) $actor->getKey(),
                    TreasuryMovement::class,
                    (string) $operation->getKey(),
                    'transfer',
                    (string) $operation->getKey(),
                    1,
                    'treasury',
                );
                $movement = TreasuryMovement::query()->create([
                    'movement_number' => 'TRS-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
                    'source_treasury_account_id' => $lockedSource->getKey(),
                    'destination_treasury_account_id' => $lockedDestination->getKey(),
                    'branch_id' => $lockedSource->branch_id ?? $lockedDestination->branch_id,
                    'created_by_account_id' => $actor->getKey(),
                    'operation_request_id' => $operation->getKey(),
                    'journal_entry_id' => $journal->getKey(),
                    'movement_type' => 'transfer',
                    'amount_kobo' => $amountKobo,
                    'reference' => $reference,
                    'memo' => $memo,
                    'occurred_at' => now(),
                ]);

                if ($counter instanceof CashCounter) {
                    CashCounterMovement::query()->create([
                        'cash_counter_id' => $counter->getKey(),
                        'treasury_movement_id' => $movement->getKey(),
                        'recorded_by_account_id' => $actor->getKey(),
                        'movement_type' => 'transfer',
                        'amount_kobo' => $amountKobo,
                        'occurred_at' => now(),
                    ]);
                }

                return $movement;
            },
        );

        if (! $result instanceof TreasuryMovement) {
            throw new \LogicException('The treasury command returned an invalid result.');
        }

        return $result;
    }
}
