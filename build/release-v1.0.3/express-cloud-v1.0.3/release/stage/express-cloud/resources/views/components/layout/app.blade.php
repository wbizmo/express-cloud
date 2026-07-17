<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description ?? 'Express Cloud by Zivora' }}">
    <title>{{ $title ?? 'Express Cloud by Zivora' }}</title>

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    {{ $slot }}

    @livewireScripts
</body>
</html>
