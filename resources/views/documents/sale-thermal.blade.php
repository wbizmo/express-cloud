<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $sale->sale_code }}</title>
<style>
@page { size: 80mm auto; margin: 3mm; }
body { width: 74mm; margin: 0; font-family: "DejaVu Sans", Arial, sans-serif; font-size: 11px; color: #000; }
.center { text-align: center; }
.rule { border-top: 1px dashed #000; margin: 8px 0; }
.row { display: flex; justify-content: space-between; gap: 8px; margin: 3px 0; }
.total { font-size: 16px; font-weight: 700; }
img.logo { max-width: 42mm; max-height: 18mm; }
img.qr { width: 34mm; height: 34mm; }
@media screen { body { margin: 20px auto; } }
</style>
</head>
<body>
<div class="center">
    @if ($settings->business_logo_path)
        <img class="logo" src="{{ public_path('storage/'.$settings->business_logo_path) }}" alt="">
    @endif
    <h1 style="font-size:16px;margin:4px 0;">{{ $settings->business_name }}</h1>
    <p style="margin:2px 0;">{{ $sale->branch?->name }}</p>
    <p style="margin:2px 0;">{{ $sale->branch?->address }}</p>
    @if ($sale->branch?->phone)<p style="margin:2px 0;">{{ $sale->branch->phone }}</p>@endif
</div>
<div class="rule"></div>
<div class="row"><span>{{ strtoupper($sale->sale_type->value) }}</span><strong>{{ $sale->sale_code }}</strong></div>
<div class="row"><span>Date</span><span>{{ $sale->created_at?->format('d M Y H:i') }}</span></div>
<div class="row"><span>Sold by</span><span>{{ $sale->soldBy?->displayName() }}</span></div>
@if ($sale->customer)<div class="row"><span>Customer</span><span>{{ $sale->customer->name }}</span></div>@endif
<div class="rule"></div>
@foreach ($sale->items as $item)
    <div style="margin-bottom:7px;">
        <strong>{{ $item->product_name_snapshot }}</strong>
        <div class="row">
            <span>
                @if ($item->track_inventory_snapshot || $item->quantity_milliunits !== 1000)
                    {{ app(\App\Services\Inventory\Quantity::class)->format($item->quantity_milliunits) }} × ₦{{ number_format($item->unit_price_kobo / 100, 2) }}
                @else
                    ₦{{ number_format($item->unit_price_kobo / 100, 2) }}
                @endif
            </span>
            <span>₦{{ number_format($item->line_total_kobo / 100, 2) }}</span>
        </div>
    </div>
@endforeach
<div class="rule"></div>
<div class="row"><span>Subtotal</span><span>₦{{ number_format($sale->subtotal_kobo / 100, 2) }}</span></div>
@if ($sale->discount_amount_kobo > 0)<div class="row"><span>Discount</span><span>-₦{{ number_format($sale->discount_amount_kobo / 100, 2) }}</span></div>@endif
@if ($sale->tax_amount_kobo > 0)<div class="row"><span>Tax</span><span>₦{{ number_format($sale->tax_amount_kobo / 100, 2) }}</span></div>@endif
<div class="row total"><span>Total</span><span>₦{{ number_format($sale->grand_total_kobo / 100, 2) }}</span></div>
<div class="row"><span>Paid</span><span>₦{{ number_format($sale->paid_amount_kobo / 100, 2) }}</span></div>
@if ($sale->balanceDueKobo() > 0)<div class="row"><span>Balance</span><span>₦{{ number_format($sale->balanceDueKobo() / 100, 2) }}</span></div>@endif
<div class="row"><span>Payment</span><span>{{ $sale->payments->pluck('paymentMethod.name')->filter()->implode(', ') ?: 'Not recorded' }}</span></div>
<div class="rule"></div>
<div class="center">
    <img class="qr" src="{{ $qrDataUri }}" alt="Verification QR">
    <p style="font-size:9px;word-break:break-all;">{{ $verificationUrl }}</p>
    <p>Thank you for your business.</p>
</div>
</body>
</html>
