@extends('layouts.app')

@section('title', 'Home')

@section('content')
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-5 md:px-8 md:py-8">
        <header class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-[1.65rem] font-bold tracking-tight md:text-3xl">{{ $greeting }}, {{ $firstName }}!</h1>
                <p class="mt-1 text-sm text-neutral-500 md:text-base">Put the work on record</p>
            </div>
        </header>

        @if ($pendingExercises->isNotEmpty())
            <a href="{{ $pendingExercisesUrl }}" class="flex items-center justify-between gap-4 rounded-3xl border border-neutral-200/80 bg-white p-4 shadow-sm transition hover:border-neutral-300">
                <span class="min-w-0">
                    <span class="block text-lg font-semibold tracking-tight">{{ $pendingExercises->count() === 1 ? '1 exercise needs approval' : $pendingExercises->count().' exercises need approval' }}</span>
                    <span class="mt-1 block truncate text-sm text-neutral-500">{{ $pendingExercises->pluck('name')->take(4)->join(', ') }}{{ $pendingExercises->count() > 4 ? '…' : '' }}</span>
                </span>
                <span class="grid size-8 shrink-0 place-items-center rounded-full text-sm font-semibold text-white" style="background-color: #e23b3b">{{ $pendingExercises->count() }}</span>
            </a>
        @endif

        <section class="rounded-3xl border border-neutral-200/80 bg-white p-3 shadow-sm md:p-4" aria-label="Week">
            <div class="mb-2 flex items-center justify-between px-1">
                <a href="{{ route('home', ['week' => $weekOffset - 1]) }}" class="grid size-9 place-items-center rounded-full text-neutral-500 hover:bg-neutral-100 hover:text-black" aria-label="Previous week">
                    <x-app-icon name="chevron-left" class="size-5" />
                </a>
                @if ($weekRange)
                    <p class="text-xs font-semibold tracking-wide text-neutral-400">{{ $weekRange }}</p>
                @else
                    <p class="text-xs font-semibold uppercase tracking-wide text-neutral-400">This week</p>
                @endif
                <a href="{{ route('home', ['week' => $weekOffset + 1]) }}" class="grid size-9 place-items-center rounded-full text-neutral-500 hover:bg-neutral-100 hover:text-black" aria-label="Next week">
                    <x-app-icon name="chevron-right" class="size-5" />
                </a>
            </div>

            <ol class="grid grid-cols-7 gap-1">
                @foreach ($week as $day)
                    <li class="flex flex-col items-center gap-1.5 py-1">
                        <span class="text-[11px] font-medium text-neutral-400">{{ $day['date']->format('D') }}</span>
                        @if ($day['workoutHref'] && $day['runHref'])
                            <span @class([
                                'grid size-9 place-items-center rounded-full text-sm font-semibold',
                                'bg-black text-white' => $day['date']->isToday(),
                                'text-neutral-800' => ! $day['date']->isToday(),
                            ]) @if ($day['date']->isToday()) aria-current="date" @endif>{{ $day['date']->format('j') }}</span>
                            <a href="{{ $day['workoutHref'] }}" class="text-[10px] font-semibold leading-none text-neutral-800" aria-label="Open workout for {{ $day['date']->format('M j') }}">Workout</a>
                            <a href="{{ $day['runHref'] }}" class="text-[10px] font-semibold leading-none text-[#2f6bff]" aria-label="Open run for {{ $day['date']->format('M j') }}">Run</a>
                        @else
                            <a href="{{ $day['href'] }}" @class([
                                'grid size-9 place-items-center rounded-full text-sm font-semibold',
                                'bg-black text-white' => $day['date']->isToday(),
                                'text-neutral-800' => ! $day['date']->isToday(),
                            ]) @if ($day['date']->isToday()) aria-current="date" @endif>
                                {{ $day['date']->format('j') }}
                            </a>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>

        @if ($missingOneRepMax)
            <a href="{{ route('one-rep-max') }}" class="flex items-center justify-between gap-4 rounded-3xl border border-neutral-200/80 bg-white p-4 shadow-sm transition hover:border-neutral-300">
                <span>
                    <span class="block text-lg font-semibold tracking-tight">Add your one rep max</span>
                    <span class="mt-1 block text-sm text-neutral-500">{{ $missingOneRepMax }}</span>
                </span>
                <x-app-icon name="chevron-right" class="size-5 shrink-0 text-neutral-400" />
            </a>
        @endif

        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
            <section>
                <h2 class="mb-3 text-sm font-semibold">Today's Plan</h2>
                @if ($todayWorkoutHref && $todayRunHref)
                    <div class="rounded-3xl border border-neutral-200/80 bg-white p-4 shadow-sm">
                        <p class="text-lg font-semibold tracking-tight">{{ $todayIsComplete ? 'View Workout' : 'Continue logging' }}</p>
                        @if ($todayPlan !== [])
                            <p class="mt-1 text-sm text-neutral-500">{{ implode(' · ', $todayPlan) }}</p>
                        @endif
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="{{ $todayWorkoutHref }}" class="inline-flex h-9 items-center rounded-full bg-neutral-900 px-3 text-sm font-semibold text-white">Open workout</a>
                            <a href="{{ $todayRunHref }}" class="inline-flex h-9 items-center rounded-full bg-neutral-100 px-3 text-sm font-semibold text-neutral-800">Open run</a>
                        </div>
                    </div>
                @else
                <a href="{{ $todayWorkoutHref ?? $todayRunHref }}" class="flex items-center gap-4 rounded-3xl border border-neutral-200/80 bg-white p-4 shadow-sm transition hover:border-neutral-300">
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-neutral-100 text-black">
                        <x-app-icon name="dumbbell" class="size-6" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-lg font-semibold tracking-tight">
                            @if ($todayIsComplete)
                                View Workout
                            @elseif ($todayPlan === [])
                                Tap to start logging →
                            @elseif ($todayRunOnly)
                                View run →
                            @else
                                Continue logging →
                            @endif
                        </span>
                        @if ($todayPlan !== [])
                            <span class="mt-1 block text-sm text-neutral-500">{{ implode(' · ', $todayPlan) }}</span>
                        @endif
                    </span>
                </a>
                @endif
            </section>

            <section>
                <h2 class="mb-3 text-sm font-semibold">{{ $weekRange ?? 'This Week' }}</h2>
                <ol class="overflow-hidden rounded-3xl border border-neutral-200/80 bg-white shadow-sm">
                    @foreach ($week as $day)
                        <li class="border-b border-neutral-100 last:border-b-0 {{ $day['date']->isToday() ? 'bg-neutral-50' : '' }}" data-date="{{ $day['date']->toDateString() }}" data-completed="{{ $day['completed'] ? 'true' : 'false' }}" data-logged="{{ $day['logged'] ? 'true' : 'false' }}" data-runs="{{ $day['runs'] }}">
                            @if ($day['workoutHref'] && $day['runHref'])
                                <div class="flex items-center justify-between gap-4 px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold">
                                            {{ $day['date']->format('D') }}
                                            @if ($day['date']->isToday())
                                                <span class="ml-2 align-middle text-[10px] font-semibold uppercase tracking-wide text-neutral-400">Today</span>
                                            @endif
                                        </p>
                                        <p class="text-sm text-neutral-500">{{ $day['summary'] ?? $day['date']->format('M j') }}</p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <a href="{{ $day['workoutHref'] }}" class="inline-flex h-8 items-center rounded-full bg-neutral-900 px-3 text-sm font-semibold text-white">Workout</a>
                                        <a href="{{ $day['runHref'] }}" class="inline-flex h-8 items-center rounded-full bg-neutral-100 px-3 text-sm font-semibold text-neutral-800">Run</a>
                                    </div>
                                </div>
                            @else
                            <a href="{{ $day['workoutHref'] ?? $day['runHref'] }}" class="flex items-center justify-between gap-4 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="font-semibold">
                                        {{ $day['date']->format('D') }}
                                        @if ($day['date']->isToday())
                                            <span class="ml-2 align-middle text-[10px] font-semibold uppercase tracking-wide text-neutral-400">Today</span>
                                        @endif
                                    </p>
                                    <p class="text-sm text-neutral-500">{{ $day['summary'] ?? $day['date']->format('M j') }}</p>
                                </div>
                                @if ($day['completed'])
                                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-emerald-500 text-white" aria-label="Completed">
                                        <x-app-icon name="check" class="size-4" />
                                    </span>
                                @elseif ($day['logged'] || $day['runs'] > 0)
                                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-neutral-200 text-[10px] font-semibold text-neutral-700" aria-label="Logged">{{ $day['runs'] > 0 && ! $day['logged'] ? 'Run' : 'Log' }}</span>
                                @else
                                    <span class="size-7 shrink-0 rounded-full border-2 border-neutral-300" aria-label="Not completed"></span>
                                @endif
                            </a>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
@endsection
