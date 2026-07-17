<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $sale->sale_code }}</title>
<style>
@page { margin: 22mm 16mm; }
body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 12px; color: #0f172a; }
.header { width:100%; border-collapse:collapse; margin-bottom:24px; }
.header td { vertical-align:top; }
.logo { max-width:170px; max-height:70px; }
h1 { font-size:28px; margin:0 0 8px; }
.meta { color:#475569; line-height:1.6; }
.items { width:100%; border-collapse:collapse; margin-top:22px; }
.items th { background:#e2e8f0; text-align:left; padding:9px; font-size:10px; text-transform:uppercase; }
.items td { border-bottom:1px solid #e2e8f0; padding:10px 9px; }
.right { text-align:right; }
.summary { margin-left:auto; margin-top:20px; width:310px; border-collapse:collapse; }
.summary td { padding:6px; }
.grand { font-size:18px; font-weight:700; border-top:2px solid #0f172a; }
.footer { margin-top:34px; border-top:1px solid #cbd5e1; padding-top:18px; }
.qr { width:120px; height:120px; }
</style>
</head>
<body>
<table class="header">
<tr>
<td>
@if ($settings->business_logo_path)
<img class="logo" src="{{ public_path('storage/'.$settings->business_logo_path) }}" alt="">
@endif
<h2>{{ $settings->business_name }}</h2>
<div class="meta">{{ $sale->branch?->name }}<br>{{ $sale->branch?->address }}<br>{{ $sale->branch?->phone }}</div>
</td>
<td class="right">
<h1>{{ strtoupper($sale->sale_type->value) }}</h1>
<div class="meta">
<strong>{{ $sale->sale_code }}</strong><br>
Date: {{ $sale->sale_date?->format('d M Y') }}<br>
Status: {{ ucfirst($sale->status->value) }}<br>
Sold by: {{ $sale->soldBy?->displayName() }}
</div>
</td>
</tr>
</table>
@if ($sale->customer)
<div>
<strong>Bill to</strong><br>
{{ $sale->customer->name }}<br>
{{ $sale->customer->phone }}<br>
{{ $sale->customer->address }}
</div>
@endif
<table class="items">
<thead><tr><th>Item</th><th>SKU</th><th>Qty</th><th class="right">Unit price</th><th class="right">Discount</th><th class="right">Tax</th><th class="right">Total</th></tr></thead>
<tbody>
@foreach ($sale->items as $item)
<tr>
<td>{{ $item->product_name_snapshot }}</td>
<td>{{ $item->sku_snapshot }}</td>
<td>{{ app(\App\Services\Inventory\Quantity::class)->format($item->quantity_milliunits) }}</td>
<td class="right">₦{{ number_format($item->unit_price_kobo / 100, 2) }}</td>
<td class="right">₦{{ number_format($item->discount_amount_kobo / 100, 2) }}</td>
<td class="right">₦{{ number_format($item->tax_amount_kobo / 100, 2) }}</td>
<td class="right">₦{{ number_format($item->line_total_kobo / 100, 2) }}</td>
</tr>
@endforeach
</tbody>
</table>
<table class="summary">
<tr><td>Subtotal</td><td class="right">₦{{ number_format($sale->subtotal_kobo / 100, 2) }}</td></tr>
<tr><td>Discount</td><td class="right">₦{{ number_format($sale->discount_amount_kobo / 100, 2) }}</td></tr>
<tr><td>Tax</td><td class="right">₦{{ number_format($sale->tax_amount_kobo / 100, 2) }}</td></tr>
<tr class="grand"><td>Grand total</td><td class="right">₦{{ number_format($sale->grand_total_kobo / 100, 2) }}</td></tr>
<tr><td>Paid</td><td class="right">₦{{ number_format($sale->paid_amount_kobo / 100, 2) }}</td></tr>
<tr><td>Balance due</td><td class="right">₦{{ number_format($sale->balanceDueKobo() / 100, 2) }}</td></tr>
</table>
<div class="footer">
<table width="100%"><tr>
<td>
<strong>Payment methods</strong><br>
{{ $sale->payments->pluck('paymentMethod.name')->filter()->implode(', ') ?: 'Not recorded' }}
@if ($sale->notes)<p><strong>Notes</strong><br>{{ $sale->notes }}</p>@endif
</td>
<td class="right"><img class="qr" src="{{ $qrDataUri }}" alt="Verification QR"></td>
</tr></table>
</div>
</body>
</html>
