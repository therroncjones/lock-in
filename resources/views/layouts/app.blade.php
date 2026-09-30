<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@isset($title){{ $title }}@else @yield('title', 'Home') @endisset — {{ config('app.name') }}</title>
        @include('partials.share-meta')
        @fonts
        <script src="https://kit.fontawesome.com/c4118a5df7.js" crossorigin="anonymous"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @media (width >= 48rem) {
                .app-frame {
                    height: 100dvh;
                    min-height: 0;
                    overflow: hidden;
                }

                .app-sidebar {
                    height: 100dvh;
                    min-height: 0;
                    overflow: hidden;
                }

                .app-main {
                    height: 100dvh;
                    min-height: 0;
                    overflow-y: auto;
                }
            }
        </style>
    </head>
    <body class="app-frame bg-[#eef2f6] text-neutral-950 antialiased">
        @php
            $missingOneRepMaxCount = \App\Support\CoreLifts::missingCount(auth()->user());
        @endphp
        <div class="flex items-center justify-end gap-3 border-b border-neutral-200/80 bg-white px-4 py-3 md:hidden">
            @if (auth()->user()->isAdministrator())
                <a href="{{ url('/admin') }}" class="text-sm font-medium text-neutral-500 hover:text-black">Users</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm font-medium text-neutral-500 hover:text-black">Sign out</button>
            </form>
        </div>
        <div class="app-frame min-h-dvh md:grid md:min-h-0 md:grid-cols-[16.5rem_minmax(0,1fr)]">
            <aside class="app-sidebar hidden border-r border-neutral-200/80 bg-white md:flex md:min-h-0 md:flex-col">
                <div class="px-6 py-6">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-lg font-black tracking-tight">
                        <x-app-icon name="lock" class="size-5" />
                        LOCK IN
                    </a>
                </div>

                @include('partials.navigation', ['variant' => 'sidebar', 'missingOneRepMaxCount' => $missingOneRepMaxCount])

                <div class="mt-auto shrink-0 border-t border-neutral-200/80 p-4">
                    <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                    <div class="mt-2 flex items-center gap-3 text-sm">
                        @if (auth()->user()->isAdministrator())
                            <a href="{{ url('/admin') }}" class="text-neutral-500 hover:text-black">Users</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-neutral-500 hover:text-black">Sign out</button>
                        </form>
                    </div>
                </div>
            </aside>

            <div class="app-main min-w-0 pb-[calc(4.75rem+env(safe-area-inset-bottom))] md:min-h-0 md:pb-0">
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </div>
        </div>

        <div class="fixed inset-x-0 bottom-0 border-t border-neutral-200 bg-white/95 backdrop-blur md:hidden" style="padding-bottom: env(safe-area-inset-bottom)">
            @include('partials.navigation', ['variant' => 'tabs', 'missingOneRepMaxCount' => $missingOneRepMaxCount])
        </div>
    </body>
</html>
