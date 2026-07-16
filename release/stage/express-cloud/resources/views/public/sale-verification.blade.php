<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Verified sale</title></head>
<body style="margin:0;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
<main style="max-width:720px;margin:40px auto;padding:24px;">
<section style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:28px;">
<h1 style="margin-top:0;">Verified transaction</h1>
<p><strong>{{ $sale->sale_code }}</strong></p>
<p>{{ ucfirst($sale->sale_type->value) }} · {{ ucfirst($sale->status->value) }}</p>
<p>{{ $sale->branch?->name }} — {{ $sale->branch?->address }}</p>
@if ($sale->customer)<p>Customer: {{ $sale->customer->name }}</p>@endif
<hr style="border:0;border-top:1px solid #e2e8f0;">
@foreach ($sale->items as $item)
<div style="display:flex;justify-content:space-between;gap:20px;margin:10px 0;">
<span>{{ $item->product_name_snapshot }}</span>
<strong>₦{{ number_format($item->line_total_kobo / 100, 2) }}</strong>
</div>
@endforeach
<hr style="border:0;border-top:1px solid #e2e8f0;">
<p style="font-size:22px;font-weight:700;">Total: ₦{{ number_format($sale->grand_total_kobo / 100, 2) }}</p>
</section>
</main>
</body>
</html>
