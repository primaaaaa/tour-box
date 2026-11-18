<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-900 text-slate-100 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <title>{{ $title ?? 'TourBox - Buat Tur 360 Derajat Mudah & Cepat' }}</title>

    <!-- Consolidated Stylesheet (Works in any hosting & environment) -->
    @if (file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        <script src="{{ asset('js/app.js') }}" defer></script>
    @endif

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="h-full flex flex-col font-sans selection:bg-indigo-500 selection:text-white" x-data="{ mobileMenuOpen: false }">

    @unless($hideNav ?? false)
        <x-navbar />
    @endunless

    <x-flash-messages />

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

</body>
</html>
