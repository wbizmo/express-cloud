<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $businessName }} End-of-Day Digest</title>
</head>
<body style="margin:0;background:#f8fafc;color:#0f172a;font-family:Arial,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;padding:24px;">
    <tr>
        <td align="center">
            <table role="presentation" width="680" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;">
                <tr>
                    <td style="padding:28px 32px;border-bottom:1px solid #e2e8f0;">
                        <div style="font-size:20px;font-weight:700;">{{ $businessName }}</div>
                        <div style="margin-top:6px;color:#64748b;font-size:14px;">End-of-day operations digest for {{ $summary['business_date'] }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px 32px;">
                        <p style="margin:0 0 6px;color:#64748b;font-size:13px;">Total sales</p>
                        <p style="margin:0 0 28px;font-size:28px;font-weight:700;">₦{{ number_format(((int) $summary['total_sales_kobo']) / 100, 2) }}</p>

                        <h2 style="font-size:16px;margin:0 0 12px;">Sales by branch</h2>
                        @forelse ($summary['sales_by_branch'] as $row)
                            <p style="margin:0 0 8px;">{{ $row['branch_name'] }}: ₦{{ number_format(((int) $row['total_kobo']) / 100, 2) }}</p>
                        @empty
                            <p style="color:#64748b;">No completed sales today.</p>
                        @endforelse

                        <h2 style="font-size:16px;margin:28px 0 12px;">Payment methods</h2>
                        @forelse ($summary['payments'] as $row)
                            <p style="margin:0 0 8px;">{{ $row['method_name'] }}: ₦{{ number_format(((int) $row['total_kobo']) / 100, 2) }}</p>
                        @empty
                            <p style="color:#64748b;">No payments recorded today.</p>
                        @endforelse

                        <h2 style="font-size:16px;margin:28px 0 12px;">Top selling items</h2>
                        @forelse ($summary['top_items'] as $row)
                            <p style="margin:0 0 8px;">{{ $row['product_name'] }}: {{ app(\App\Services\Inventory\Quantity::class)->format((int) $row['units_milliunits']) }} units</p>
                        @empty
                            <p style="color:#64748b;">No item sales today.</p>
                        @endforelse

                        <h2 style="font-size:16px;margin:28px 0 12px;">Staff performance</h2>
                        @forelse ($summary['staff'] as $row)
                            <p style="margin:0 0 8px;">{{ $row['name'] }}: {{ $row['sales_count'] }} sales, ₦{{ number_format(((int) $row['revenue_kobo']) / 100, 2) }}</p>
                        @empty
                            <p style="color:#64748b;">No staff sales today.</p>
                        @endforelse

                        <h2 style="font-size:16px;margin:28px 0 12px;">Low-stock items</h2>
                        @forelse ($summary['low_stock'] as $row)
                            <p style="margin:0 0 8px;">{{ $row['branch_name'] }} — {{ $row['product_name'] }} ({{ app(\App\Services\Inventory\Quantity::class)->format((int) $row['quantity_milliunits']) }} remaining)</p>
                        @empty
                            <p style="color:#64748b;">No open low-stock alerts.</p>
                        @endforelse
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
