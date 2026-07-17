<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Commercial;

use App\Enums\Commercial\DiscountValueType;
use App\Enums\Commercial\VoucherStatus;
use App\Http\Requests\Commercial\StoreVoucherRequest;
use App\Models\Account;
use App\Models\DiscountVoucher;
use App\Services\Catalog\MoneyInput;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final readonly class VoucherController
{
    public function __construct(
        private MoneyInput $money,
        private AuditLogger $audit,
    ) {}

    public function index(): View
    {
        return view('admin.commercial.vouchers', [
            'vouchers' => DiscountVoucher::query()
                ->orderByDesc('created_at')
                ->paginate(40),
        ]);
    }

    public function store(
        StoreVoucherRequest $request,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();
        $type = DiscountValueType::from(
            $request->string('value_type')->toString(),
        );

        $value = $type === DiscountValueType::Percentage
            ? (int) round($request->float('value') * 100)
            : ($this->money->toKobo($request->input('value')) ?? 0);

        $voucher = DiscountVoucher::query()->create([
            'code' => mb_strtoupper(
                $request->string('code')->trim()->toString(),
            ),
            'name' => $request->string('name')->trim()->toString(),
            'value_type' => $type,
            'value' => $value,
            'minimum_sale_kobo' => $this->money->toKobo(
                $request->input('minimum_sale'),
            ) ?? 0,
            'maximum_discount_kobo' => $this->money->toKobo(
                $request->input('maximum_discount'),
            ),
            'usage_limit' => $request->integer('usage_limit') ?: null,
            'starts_at' => $request->date('starts_at'),
            'ends_at' => $request->date('ends_at'),
            'status' => $request->boolean('is_active', true)
                ? VoucherStatus::Active
                : VoucherStatus::Inactive,
            'created_by_account_id' => $actor->getKey(),
        ]);

        $this->audit->record(
            $request,
            'voucher.created',
            'discount_voucher',
            $voucher,
            after: $voucher->only([
                'code',
                'value_type',
                'value',
                'usage_limit',
                'status',
            ]),
        );

        return back()->with('status', 'Voucher created.');
    }

    public function toggle(
        StoreVoucherRequest $request,
        DiscountVoucher $voucher,
    ): RedirectResponse {
        $voucher->forceFill([
            'status' => $request->boolean('is_active')
                ? VoucherStatus::Active
                : VoucherStatus::Inactive,
        ])->save();

        return back()->with('status', 'Voucher status updated.');
    }
}
