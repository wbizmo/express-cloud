<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $report->title }} {{ $report->reference }}</title>
<style>
@page { margin: 32px; }
body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 11px; }
.header { display: table; width: 100%; border-bottom: 2px solid #172033; padding-bottom: 14px; margin-bottom: 20px; }
.header > div { display: table-cell; vertical-align: top; }
.logo { width: 120px; max-height: 70px; object-fit: contain; }
.right { text-align: right; }
h1 { margin: 0 0 4px; font-size: 22px; }
.muted { color: #64748b; }
table { width: 100%; border-collapse: collapse; margin-top: 16px; }
th { background: #eef2f7; text-align: left; padding: 8px; border-bottom: 1px solid #cbd5e1; }
td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
.summary { width: 46%; margin-left: auto; margin-top: 18px; }
.summary td:first-child { font-weight: 700; }
.footer { margin-top: 28px; padding-top: 12px; border-top: 1px solid #cbd5e1; font-size: 9px; color: #64748b; }
</style>
</head>
<body>
<div class="header">
<div>
@if ($branding['logo_data_uri'])
<img class="logo" src="{{ $branding['logo_data_uri'] }}" alt="">
@endif
<h2>{{ $branding['business_name'] }}</h2>
@if ($branding['address'])<div>{{ $branding['address'] }}</div>@endif
@if ($branding['phone'])<div>{{ $branding['phone'] }}</div>@endif
@if ($branding['email'])<div>{{ $branding['email'] }}</div>@endif
</div>
<div class="right">
<h1>{{ $report->title }}</h1>
<div><strong>{{ $report->reference }}</strong></div>
<div class="muted">{{ $report->date }}</div>
</div>
</div>

@if ($report->rows)
<table>
<thead>
<tr>
@foreach (array_keys($report->rows[0]) as $heading)
<th>{{ $heading }}</th>
@endforeach
</tr>
</thead>
<tbody>
@foreach ($report->rows as $row)
<tr>
@foreach ($row as $value)
<td>{{ $value }}</td>
@endforeach
</tr>
@endforeach
</tbody>
</table>
@endif

<table class="summary">
<tbody>
@foreach ($report->summary as $label => $value)
<tr><td>{{ $label }}</td><td>{{ $value }}</td></tr>
@endforeach
</tbody>
</table>

@if ($report->notes)
<p><strong>Notes:</strong> {{ $report->notes }}</p>
@endif

<div class="footer">
@if ($branding['receipt_footer'])<div>{{ $branding['receipt_footer'] }}</div>@endif
@if ($branding['document_terms'])<div>{{ $branding['document_terms'] }}</div>@endif
<div>Generated {{ now()->format('d M Y H:i:s') }} · SHA-256 logged in Express Cloud.</div>
</div>
</body>
</html>
