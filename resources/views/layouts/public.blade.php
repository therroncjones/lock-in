<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@isset($title){{ $title }}@else @yield('title', 'One Rep Max') @endisset — {{ config('app.name') }}</title>
        @fonts
        <script src="https://kit.fontawesome.com/c4118a5df7.js" crossorigin="anonymous"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#eef2f6] text-neutral-950 antialiased">
        <header class="border-b border-neutral-200/80 bg-white">
            <div class="mx-auto flex w-full max-w-5xl items-center justify-between gap-4 px-4 py-4 md:px-8">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-lg font-black tracking-tight">
                    <x-app-icon name="lock" class="size-5" />
                    LOCK IN
                </a>
                <a href="{{ route('filament.admin.auth.login') }}" class="text-sm font-medium text-neutral-500 hover:text-black">Sign in</a>
            </div>
        </header>

        <main>
            @isset($slot)
                {{ $slot }}
            @else
                @yield('content')
            @endisset
        </main>
    </body>
</html>
