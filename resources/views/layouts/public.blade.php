<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@isset($title){{ $title }}@else @yield('title', 'One Rep Max') @endisset — {{ config('app.name') }}</title>
        @include('partials.share-meta')
        @fonts
        <script src="https://kit.fontawesome.com/c4118a5df7.js" crossorigin="anonymous"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @media (width < 40rem) {
                .public-signup-long {
                    display: none;
                }
            }

            @media (width >= 40rem) {
                .public-signup-short {
                    display: none;
                }
            }

            @media (width < 64rem) {
                .landing-desktop-only {
                    display: none;
                }

                .landing-lead {
                    margin-bottom: 1.25rem;
                }

                .landing-signup {
                    height: auto;
                    padding: 0.7rem 1.15rem;
                    font-size: 0.95rem;
                }
            }

            @media (width >= 64rem) {
                .landing-grid {
                    display: grid;
                    grid-template-columns: minmax(16rem, 0.82fr) minmax(0, 1.18fr);
                    gap: 2.5rem;
                    align-items: start;
                }

                .landing-title {
                    font-size: 3.25rem;
                    line-height: 1.05;
                    letter-spacing: -0.03em;
                }

                .landing-signup {
                    height: auto;
                    padding: 0.85rem 1.4rem;
                    font-size: 1rem;
                }
            }
        </style>
    </head>
    <body class="bg-[#eef2f6] text-neutral-950 antialiased">
        <header>
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-4 md:px-8">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-lg font-black tracking-tight">
                    <x-app-icon name="lock" class="size-5" />
                    LOCK IN
                </a>
                <div class="flex items-center gap-3">
                    <a href="{{ route('filament.admin.auth.login') }}" class="public-sign-in text-sm font-medium text-neutral-500 hover:text-black">Sign in</a>
                    <a href="{{ route('filament.admin.auth.register') }}" class="public-signup-short landing-signup inline-flex h-9 shrink-0 items-center rounded-xl bg-[#2f6bff] px-3 text-sm font-semibold text-white">Sign Up</a>
                    <a href="{{ route('filament.admin.auth.register') }}" class="public-signup-long landing-signup inline-flex h-9 shrink-0 items-center rounded-xl bg-[#2f6bff] px-3 text-sm font-semibold text-white">Sign Up</a>
                </div>
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
