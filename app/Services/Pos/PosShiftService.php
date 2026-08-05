<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Models\Account;
use App\Models\CommercialApprovalRequest;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PosCashMovement;
use App\Models\PosHeldSale;
use App\Models\PosReceiptPrint;
use App\Models\PosShift;
use App\Models\PosShiftTender;
use App\Models\PosTerminal;
use App\Models\Sale;
use App\Services\Governance\CommercialApprovalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class PosShiftService
{
    public function __construct(private CommercialApprovalService $approvals) {}

    public function open(
        PosTerminal $terminal,
        Account $cashier,
        int $openingFloatKobo,
    ): PosShift {
        if ($openingFloatKobo < 0) {
            throw new \DomainException('Opening float cannot be negative.');
        }

        return DB::transaction(function () use ($terminal, $cashier, $openingFloatKobo): PosShift {
            /** @var PosTerminal $locked */
            $locked = PosTerminal::query()->whereKey($terminal->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'active') {
                throw new \DomainException('This POS terminal is not active.');
            }
            $existing = PosShift::query()
                ->where('pos_terminal_id', $locked->getKey())
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();
            if ($existing instanceof PosShift) {
                throw new \DomainException('The POS terminal already has an open shift.');
            }

            $shift = PosShift::query()->create([
                'shift_number' => 'SHIFT-'.now()->format('ymd-His').'-'.Str::upper(Str::random(4)),
                'pos_terminal_id' => $locked->getKey(),
                'branch_id' => $locked->branch_id,
                'cashier_account_id' => $cashier->getKey(),
                'status' => 'open',
                'opening_float_kobo' => $openingFloatKobo,
                'expected_cash_kobo' => $openingFloatKobo,
                'opened_at' => now(),
            ]);

            if ($openingFloatKobo > 0) {
                $this->recordMovement($shift, $cashier, 'opening_float', $openingFloatKobo, 'Opening float');
            }

            return $shift;
        }, 3);
    }

    public function recordMovement(
        PosShift $shift,
        Account $actor,
        string $type,
        int $amountKobo,
        string $memo,
    ): PosCashMovement {
        if (! in_array($type, ['opening_float', 'pay_in', 'pay_out', 'cash_refund'], true)) {
            throw new \DomainException('Unsupported POS cash movement type.');
        }
        if ($amountKobo <= 0 || trim($memo) === '') {
            throw new \DomainException('A positive amount and memo are required.');
        }
        if (! $shift->isOpen()) {
            throw new \DomainException('Cash movements require an open shift.');
        }

        return PosCashMovement::query()->create([
            'pos_shift_id' => $shift->getKey(),
            'recorded_by_account_id' => $actor->getKey(),
            'movement_type' => $type,
            'amount_kobo' => $amountKobo,
            'memo' => trim($memo),
            'recorded_at' => now(),
        ]);
    }

    /** @param array<int, mixed> $cart */
    public function hold(
        PosShift $shift,
        Account $actor,
        array $cart,
        int $estimatedTotalKobo,
        ?string $customerId,
    ): PosHeldSale {
        if (! $shift->isOpen()) {
            throw new \DomainException('Sales may only be held during an open shift.');
        }
        if ($cart === []) {
            throw new \DomainException('An empty cart cannot be held.');
        }

        return PosHeldSale::query()->create([
            'hold_token' => 'HOLD-'.Str::upper(Str::random(12)),
            'pos_shift_id' => $shift->getKey(),
            'customer_id' => $customerId,
            'held_by_account_id' => $actor->getKey(),
            'cart_payload' => $cart,
            'estimated_total_kobo' => max(0, $estimatedTotalKobo),
            'status' => 'held',
            'held_at' => now(),
        ]);
    }

    public function resume(PosHeldSale $held, PosShift $shift): PosHeldSale
    {
        return DB::transaction(function () use ($held, $shift): PosHeldSale {
            /** @var PosHeldSale $locked */
            $locked = PosHeldSale::query()->whereKey($held->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'held' || (string) $locked->pos_shift_id !== (string) $shift->getKey()) {
                throw new \DomainException('This held sale cannot be resumed in the selected shift.');
            }
            $locked->forceFill(['status' => 'resumed', 'resumed_at' => now()])->save();

            return $locked;
        }, 3);
    }

    /** @param array<string, int> $countedByPaymentMethod */
    public function close(
        PosShift $shift,
        Account $actor,
        array $countedByPaymentMethod,
        string $note,
        ?CommercialApprovalRequest $varianceApproval = null,
    ): PosShift {
        return DB::transaction(function () use ($shift, $actor, $countedByPaymentMethod, $note, $varianceApproval): PosShift {
            /** @var PosShift $locked */
            $locked = PosShift::query()->whereKey($shift->getKey())->lockForUpdate()->firstOrFail();
            if (! $locked->isOpen()) {
                throw new \DomainException('Only an open shift may be closed.');
            }

            $payments = Payment::query()
                ->join('sales', 'sales.id', '=', 'payments.sale_id')
                ->where('sales.pos_shift_id', $locked->getKey())
                ->selectRaw('payments.payment_method_id, SUM(payments.amount_kobo) AS total_kobo')
                ->groupBy('payments.payment_method_id')
                ->pluck('total_kobo', 'payment_method_id');
            $cashAdjustments = PosCashMovement::query()
                ->where('pos_shift_id', $locked->getKey())
                ->get()
                ->sum(static function (PosCashMovement $movement): int {
                    return in_array($movement->movement_type, ['opening_float', 'pay_in'], true)
                        ? $movement->amount_kobo
                        : -$movement->amount_kobo;
                });

            $totalVariance = 0;
            $expectedCash = $cashAdjustments;
            foreach (PaymentMethod::query()->where('is_visible_in_pos', true)->orderBy('id')->get() as $method) {
                $expected = (int) ($payments[(string) $method->getKey()] ?? 0);
                if ($method->method_type === 'cash') {
                    $expected += $cashAdjustments;
                    $expectedCash = $expected;
                }
                $counted = max(0, (int) ($countedByPaymentMethod[(string) $method->getKey()] ?? 0));
                $variance = $counted - $expected;
                $totalVariance += $variance;
                PosShiftTender::query()->updateOrCreate(
                    [
                        'pos_shift_id' => $locked->getKey(),
                        'payment_method_id' => $method->getKey(),
                    ],
                    [
                        'expected_amount_kobo' => $expected,
                        'counted_amount_kobo' => $counted,
                        'variance_kobo' => $variance,
                    ],
                );
            }

            $threshold = (int) config('pos.variance_approval_threshold_kobo', 1000);
            if (abs($totalVariance) > $threshold) {
                if (! $varianceApproval instanceof CommercialApprovalRequest
                    || $varianceApproval->status !== 'approved') {
                    $this->approvals->request(
                        'pos.shift.variance',
                        $locked,
                        $actor,
                        ['variance_kobo' => $totalVariance],
                        trim($note) === '' ? 'POS shift variance requires review.' : $note,
                        (string) $locked->branch_id,
                    );
                    throw new \DomainException('The shift variance requires an approved manager request before closing.');
                }
            }

            $declaredCash = 0;
            $cashMethodIds = PaymentMethod::query()->where('method_type', 'cash')->pluck('id');
            foreach ($cashMethodIds as $cashMethodId) {
                $declaredCash += max(0, (int) ($countedByPaymentMethod[(string) $cashMethodId] ?? 0));
            }
            $locked->forceFill([
                'status' => 'closed',
                'closed_by_account_id' => $actor->getKey(),
                'expected_cash_kobo' => $expectedCash,
                'declared_cash_kobo' => $declaredCash,
                'cash_variance_kobo' => $declaredCash - $expectedCash,
                'closed_at' => now(),
                'closing_note' => trim($note),
            ])->save();

            return $locked;
        }, 3);
    }

    public function recordPrint(
        Sale $sale,
        Account $actor,
        string $format,
        ?PosShift $shift = null,
        ?string $reason = null,
        ?CommercialApprovalRequest $approval = null,
    ): PosReceiptPrint {
        if (! in_array($format, config('pos.allowed_receipt_formats', []), true)) {
            throw new \DomainException('Unsupported receipt format.');
        }
        $copy = PosReceiptPrint::query()->where('sale_id', $sale->getKey())->lockForUpdate()->count() + 1;
        $isReprint = $copy > 1;
        $approvalAfter = (int) config('pos.reprint_requires_approval_after', 1);
        if ($copy > $approvalAfter && (! $approval instanceof CommercialApprovalRequest || $approval->status !== 'approved')) {
            throw new \DomainException('Receipt reprints require an approved request.');
        }

        return PosReceiptPrint::query()->create([
            'sale_id' => $sale->getKey(),
            'pos_shift_id' => $shift?->getKey(),
            'printed_by_account_id' => $actor->getKey(),
            'approval_request_id' => $approval?->getKey(),
            'format' => $format,
            'copy_number' => $copy,
            'is_reprint' => $isReprint,
            'reason' => $isReprint ? trim((string) $reason) : null,
            'printed_at' => now(),
        ]);
    }
}
