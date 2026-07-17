<?php

declare(strict_types=1);

namespace App\Actions\AccountingOperations;

use App\Models\Account;
use App\Models\StandaloneReceipt;
use App\Services\Catalog\MoneyInput;
use App\Services\Organisation\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateStandaloneReceipt
{
    public function __construct(
        private MoneyInput $money,
        private AuditLogger $audit,
    ) {}

    /**
     * @param array{
     *   branch_id:string,
     *   customer_id?:string|null,
     *   payment_method_id:string,
     *   payer_name:string,
     *   payer_phone?:string|null,
     *   amount:mixed,
     *   reference?:string|null,
     *   purpose:string,
     *   notes?:string|null,
     *   received_at:string
     * } $data
     */
    public function execute(
        Request $request,
        Account $actor,
        array $data,
    ): StandaloneReceipt {
        return DB::transaction(function () use (
            $request,
            $actor,
            $data,
        ): StandaloneReceipt {
            $receipt = StandaloneReceipt::query()->create([
                'receipt_number' => 'RCP-'.now()->format('ymd').'-'
                    .Str::upper(Str::random(6)),
                'branch_id' => $data['branch_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'payment_method_id' => $data['payment_method_id'],
                'received_by_account_id' => $actor->getKey(),
                'payer_name' => $data['payer_name'],
                'payer_phone' => $data['payer_phone'] ?? null,
                'amount_kobo' => $this->money->toKobo(
                    $data['amount'],
                ) ?? 0,
                'reference' => $data['reference'] ?? null,
                'purpose' => $data['purpose'],
                'notes' => $data['notes'] ?? null,
                'status' => 'received',
                'received_at' => $data['received_at'],
            ]);

            $this->audit->record(
                $request,
                'standalone-receipt.created',
                'standalone_receipt',
                $receipt,
                after: [
                    'receipt_number' => $receipt->receipt_number,
                    'amount_kobo' => $receipt->amount_kobo,
                    'payer_name' => $receipt->payer_name,
                    'purpose' => $receipt->purpose,
                ],
            );

            return $receipt;
        }, 3);
    }
}
