@extends('layouts.public')

@section('title', 'One Rep Max Calculator')

@section('content')
    <div class="mx-auto w-full max-w-6xl px-4 py-5 md:px-8 md:py-8">
        <div class="landing-grid">
            <div>
                <h1 class="landing-title text-3xl font-bold tracking-tight">One Rep Max Calculator</h1>
                <p class="landing-lead mt-3 max-w-xl text-sm text-neutral-500">Enter a one rep max, see the plate breakdown, and start tracking your training with Lock In.</p>

                <ul class="landing-desktop-only mt-8 flex flex-col gap-4">
                    <li class="flex items-start gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-[#e8f1ff] text-[#2f6bff]">
                            <x-app-icon name="strength" class="size-5" />
                        </span>
                        <span>
                            <span class="block font-semibold">Log your workouts</span>
                            <span class="mt-1 block text-sm text-neutral-500">Sets, reps, weights, and runs.</span>
                        </span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-[#e8f1ff] text-[#2f6bff]">
                            <x-app-icon name="progress" class="size-5" />
                        </span>
                        <span>
                            <span class="block font-semibold">See real progress</span>
                            <span class="mt-1 block text-sm text-neutral-500">Loads, volume, and how the work is moving.</span>
                        </span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-[#e8f1ff] text-[#2f6bff]">
                            <x-app-icon name="calendar" class="size-5" />
                        </span>
                        <span>
                            <span class="block font-semibold">Stay consistent</span>
                            <span class="mt-1 block text-sm text-neutral-500">Strength, runs, and your maxes in one place.</span>
                        </span>
                    </li>
                </ul>

                <div class="landing-desktop-only mt-8">
                    <a href="{{ route('filament.admin.auth.register') }}" class="landing-signup inline-flex h-11 items-center rounded-xl bg-[#2f6bff] px-4 text-sm font-semibold text-white">Sign Up</a>
                </div>
            </div>

            <livewire:one-rep-max-calculator />
        </div>

        <section id="the-rest" class="mt-8 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-neutral-200/70" style="scroll-margin-top: 1.5rem" aria-label="More than a calculator">
            <p class="text-[11px] font-semibold tracking-wide text-[#2f6bff]">THE FULL EXPERIENCE</p>
            <h2 class="mt-2 text-2xl font-bold tracking-tight">More than a calculator.</h2>
            <p class="mt-2 max-w-xl text-sm text-neutral-500">Create a free account to log your workouts and runs, track progress, and keep bench, squat, and deadlift on record.</p>
            <a href="{{ route('filament.admin.auth.register') }}" class="landing-signup mt-4 inline-flex h-11 items-center rounded-xl bg-[#2f6bff] px-4 text-sm font-semibold text-white">Sign Up</a>
        </section>
    </div>
@endsection
