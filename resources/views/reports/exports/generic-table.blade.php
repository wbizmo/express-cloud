<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0F172A; }
    h1 { font-size: 16px; margin-bottom: 2px; }
    .meta { color: #64748B; font-size: 9px; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border-bottom: 1px solid #E2E8F0; padding: 5px 6px; text-align: left; }
    th { background: #F8FAFC; text-transform: uppercase; font-size: 8px; letter-spacing: 0.4px; color: #475569; }
</style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">Generated {{ $generatedAt->format('d M Y H:i') }}</div>
    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($headings) }}">No records.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
