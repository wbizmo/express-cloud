<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="googlebot" content="noindex, nofollow">
    <meta name="robots" content="noindex, nofollow">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="{{ $description ?? 'Express Cloud by Zivora' }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0B1F3A">
    <meta name="pwa-enabled" content="{{ config('resilience.enabled') ? '1' : '0' }}">
    <meta name="operation-recovery-template" content="{{ url('/admin/operations/recovery/__SCOPE__/__KEY__') }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/express-cloud.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..24,400,0,0" rel="stylesheet">
    <title>{{ $title ?? 'Express Cloud by Zivora' }}</title>

    <style>[x-cloak] { display: none !important; }</style>

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="sr-only" role="status" aria-live="polite" data-connectivity-status></div>
    {{ $slot }}

    @livewireScripts
</body>
</html>
