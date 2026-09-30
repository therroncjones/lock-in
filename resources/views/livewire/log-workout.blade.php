@php
    $typeIcons = [
        'Strength - Upper Body' => 'dumbbell',
        'Strength - Lower Body' => 'dumbbell',
        'Strength - Full Body' => 'dumbbell',
        'Cardio' => 'run',
        'Bodyweight' => 'user',
        'Conditioning' => 'timer',
        'Mobility' => 'mobility',
    ];
@endphp

<div class="mx-auto flex w-full max-w-2xl flex-col gap-4 px-4 py-4 md:px-8 md:py-8">
    <header class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-2">
            <a href="{{ route('home') }}" class="mt-1 grid size-8 place-items-center rounded-full text-neutral-700 hover:bg-white" aria-label="Back to home">
                <x-app-icon name="chevron-left" class="size-5" />
            </a>
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Workout</h1>
                <p class="text-sm text-neutral-500">{{ $view === 'month' ? $monthLabel : $formattedDate }}</p>
                @if ($view === 'day' && $isComplete)
                    <p class="mt-1 text-xs text-neutral-500">Created {{ $createdAt }}</p>
                    <p class="text-xs text-neutral-500">Updated {{ $updatedAt }}</p>
                @endif
            </div>
        </div>
        @if ($view === 'day')
        <label class="flex items-center gap-2 rounded-2xl bg-white px-3 py-2 text-sm font-medium shadow-sm ring-1 ring-neutral-200/80">
            <x-app-icon name="calendar" class="size-4 text-neutral-500" />
            <input
                id="workout-date"
                type="date"
                wire:model.live="date"
                aria-label="Date"
                class="bg-transparent text-sm outline-none"
            >
        </label>
        @endif
    </header>

    <div class="flex gap-2" aria-label="Log view">
        <button
            type="button"
            wire:click="showToday"
            @class([
                'rounded-full px-3 py-1.5 text-sm font-semibold',
                'bg-[#2f6bff] text-white' => $date === now()->toDateString(),
                'bg-white text-neutral-700 ring-1 ring-neutral-200' => $date !== now()->toDateString(),
            ])
        >Today</button>
        <div class="flex gap-2" role="tablist">
        <button
            type="button"
            wire:click="showDay"
            role="tab"
            aria-selected="{{ $view === 'day' ? 'true' : 'false' }}"
            @class([
                'rounded-full px-3 py-1.5 text-sm font-semibold',
                'bg-[#2f6bff] text-white' => $view === 'day',
                'bg-white text-neutral-700 ring-1 ring-neutral-200' => $view !== 'day',
            ])
        >Daily</button>
        <button
            type="button"
            wire:click="showMonth"
            role="tab"
            aria-selected="{{ $view === 'month' ? 'true' : 'false' }}"
            @class([
                'rounded-full px-3 py-1.5 text-sm font-semibold',
                'bg-[#2f6bff] text-white' => $view === 'month',
                'bg-white text-neutral-700 ring-1 ring-neutral-200' => $view !== 'month',
            ])
        >Monthly</button>
        </div>
    </div>

    @if ($view === 'month')
        <section class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70" aria-label="Month">
            <div class="mb-3 flex items-center justify-between">
                <button type="button" wire:click="previousMonth" class="grid size-9 place-items-center rounded-full text-neutral-500 hover:bg-neutral-100 hover:text-black" aria-label="Previous month">
                    <x-app-icon name="chevron-left" class="size-5" />
                </button>
                <p class="text-sm font-semibold">{{ $monthLabel }}</p>
                <button type="button" wire:click="nextMonth" class="grid size-9 place-items-center rounded-full text-neutral-500 hover:bg-neutral-100 hover:text-black" aria-label="Next month">
                    <x-app-icon name="chevron-right" class="size-5" />
                </button>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-medium text-neutral-400">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                    <span>{{ $weekday }}</span>
                @endforeach
            </div>

            <div class="mt-1 grid grid-cols-7 gap-1">
                @foreach ($calendar as $day)
                    @if ($day['inMonth'])
                        <button
                            type="button"
                            wire:click="selectDay('{{ $day['date'] }}')"
                            wire:key="day-{{ $day['date'] }}"
                            aria-label="{{ $day['label'] }}"
                            @if ($day['today']) aria-current="date" @endif
                            @class([
                                'flex flex-col items-center gap-1 rounded-2xl px-0.5 py-2 hover:bg-neutral-50',
                                'bg-neutral-100' => $day['selected'],
                            ])
                        >
                            <span @class([
                                'grid size-8 place-items-center rounded-full text-sm font-semibold',
                                'bg-black text-white' => $day['today'],
                                'text-neutral-800' => ! $day['today'],
                            ])>{{ $day['number'] }}</span>
                            <span class="max-w-full truncate text-[10px] font-medium text-neutral-500">{{ $day['type'] ?? ' ' }}</span>
                            @if ($day['completed'])
                                <span class="grid size-4 place-items-center rounded-full bg-emerald-500 text-white">
                                    <x-app-icon name="check" class="size-3" />
                                </span>
                            @elseif ($day['logged'])
                                <span class="size-4 rounded-full border-2 border-neutral-300"></span>
                            @else
                                <span class="size-4"></span>
                            @endif
                        </button>
                    @else
                        <span wire:key="day-{{ $day['date'] }}" class="py-2 text-center text-sm text-neutral-400">{{ $day['number'] }}</span>
                    @endif
                @endforeach
            </div>
        </section>
    @else
    <section class="flex flex-wrap items-center gap-2" aria-label="Sessions">
        @foreach ($sessions as $session)
            <button
                type="button"
                wire:key="session-{{ $session->id }}"
                wire:click="selectSession({{ $session->id }})"
                @class([
                    'rounded-full px-3 py-1.5 text-sm font-semibold',
                    'bg-black text-white' => $session->id === $currentSessionId,
                    'bg-white text-neutral-700 ring-1 ring-neutral-200' => $session->id !== $currentSessionId,
                ])
            >{{ $session->type ?: 'Session '.$loop->iteration }}</button>
        @endforeach
        @unless ($isComplete)
            <button type="button" wire:click="startSession" class="rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-neutral-700 ring-1 ring-neutral-200">Add session</button>
        @endunless
        @if ($sessions->isNotEmpty())
            <button type="button" wire:click="deleteSession" wire:confirm="Delete this session?" class="rounded-full px-3 py-1.5 text-sm font-semibold text-red-600">Delete session</button>
        @endif
    </section>
    <section class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
        <p class="text-sm font-medium text-neutral-500">Type of workout</p>
        <div class="mt-3 flex flex-wrap items-center gap-2">
            @foreach ($suggestedTypes as $suggestion)
                <button
                    type="button"
                    wire:click="setType('{{ $suggestion }}')"
                    @disabled($isComplete)
                    @class([
                        'inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold',
                        'bg-[#2f6bff] text-white' => $type === $suggestion,
                        'bg-neutral-100 text-neutral-700 hover:bg-neutral-200' => $type !== $suggestion,
                    ])
                >
                    <x-app-icon :name="$typeIcons[$suggestion] ?? 'dumbbell'" class="size-3.5" />
                    {{ $suggestion }}
                </button>
            @endforeach
        </div>
    </section>

    <section class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold tracking-tight">Exercises</h2>
            @unless ($isComplete)
                <div class="flex items-center gap-4">
                    <button type="button" wire:click="addBlock" class="inline-flex cursor-pointer items-center gap-1.5 text-sm font-semibold text-black">
                        <span class="grid size-5 place-items-center rounded-full ring-1 ring-black">
                            <x-app-icon name="log" class="size-3" />
                        </span>
                        Add Block
                    </button>
                    <button type="button" wire:click="openExercisePicker" class="inline-flex cursor-pointer items-center gap-1.5 text-sm font-semibold text-[#2f6bff]">
                        <span class="grid size-5 place-items-center rounded-full ring-1 ring-[#2f6bff]">
                            <x-app-icon name="log" class="size-3" />
                        </span>
                        Add Exercise
                    </button>
                </div>
            @endunless
        </div>

        @if ($looseExercises->isEmpty() && $blocks->isEmpty())
            <button type="button" wire:click="openExercisePicker" class="rounded-3xl border border-dashed border-neutral-300 px-4 py-6 text-center text-sm text-neutral-500 hover:bg-white">
                Add an exercise to start this log.
            </button>
        @endif

        @foreach ($looseExercises as $entry)
            @include('livewire.partials.workout-exercise', ['entry' => $entry])
        @endforeach

        @foreach ($blocks as $block)
            @php
                $blockExercises = $workoutExercises->where('workout_block_id', $block->id);
                $blockOpen = $expandedBlocks[$block->id] ?? false;
                $blockLabel = $blockNames[$block->id] ?? $block->name;
            @endphp
            <section wire:key="block-{{ $block->id }}" class="flex flex-col gap-2 rounded-3xl bg-[#f3f7ff] p-3 ring-1 ring-[#2f6bff]/20">
                <div class="flex items-start gap-2 px-1">
                    <div class="min-w-0 flex-1">
                        <input
                            type="text"
                            wire:model.blur="blockNames.{{ $block->id }}"
                            maxlength="100"
                            aria-label="Block name"
                            @readonly($isComplete)
                            class="w-full bg-transparent text-sm font-bold outline-none"
                        >
                        @error('blockNames.'.$block->id)
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @unless ($isComplete)
                            <button type="button" wire:click="removeBlock({{ $block->id }})" class="mt-1 text-sm font-medium text-neutral-500 hover:text-neutral-800">
                                Remove block
                            </button>
                        @endunless
                    </div>
                    @unless ($blockOpen)
                        <span class="shrink-0 pt-0.5 text-sm text-neutral-500">
                            {{ $blockExercises->count() }} {{ $blockExercises->count() === 1 ? 'exercise' : 'exercises' }}
                        </span>
                    @endunless
                    <button type="button" wire:click="toggleBlock({{ $block->id }})" class="grid size-8 shrink-0 place-items-center text-neutral-400 hover:text-neutral-700" aria-label="{{ $blockOpen ? 'Collapse' : 'Expand' }} {{ $blockLabel }}">
                        <x-app-icon :name="$blockOpen ? 'chevron-up' : 'chevron-down'" class="size-5" />
                    </button>
                </div>

                @if ($blockOpen)
                    @forelse ($blockExercises as $entry)
                        @include('livewire.partials.workout-exercise', ['entry' => $entry])
                    @empty
                        <p class="px-2 py-3 text-sm text-neutral-500">Add an exercise to this block.</p>
                    @endforelse

                    @unless ($isComplete)
                        <button type="button" wire:click="openExercisePicker({{ $block->id }})" class="inline-flex items-center justify-center gap-1.5 rounded-2xl py-2 text-sm font-semibold text-[#2f6bff] hover:bg-white">
                            <x-app-icon name="log" class="size-4" />
                            Add Exercise
                        </button>
                    @endunless
                @endif
            </section>
        @endforeach
    </section>

    @if ($showExercisePicker)
        <div
            class="fixed inset-0 z-50 flex items-end justify-center bg-neutral-950/40 p-4 sm:items-center"
            wire:click="closeExercisePicker"
            wire:keydown.escape.window="closeExercisePicker"
        >
            <section
                role="dialog"
                aria-modal="true"
                aria-labelledby="add-exercise-title"
                wire:click.stop
                class="flex max-h-[min(40rem,calc(100dvh-2rem))] w-full max-w-lg flex-col overflow-hidden rounded-3xl bg-white p-4 shadow-xl"
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 id="add-exercise-title" class="text-lg font-bold tracking-tight">Add Exercise</h2>
                        @if ($targetBlockName)
                            <p class="text-sm text-neutral-500">Adding to {{ $targetBlockName }}</p>
                        @endif
                    </div>
                    <button type="button" wire:click="closeExercisePicker" class="grid size-10 place-items-center rounded-full bg-neutral-900 text-white hover:bg-neutral-700" aria-label="Close add exercise">
                        <x-app-icon name="close" class="size-5" />
                    </button>
                </div>

                <label class="mt-3 flex items-center gap-2 rounded-2xl bg-neutral-100 px-3 py-2.5">
                    <x-app-icon name="search" class="size-4 text-neutral-400" />
                    <input
                        id="exercise-search"
                        type="text"
                        wire:model.live.debounce.300ms="exerciseQuery"
                        maxlength="100"
                        placeholder="Search exercises..."
                        autocomplete="off"
                        autofocus
                        class="w-full bg-transparent text-base outline-none placeholder:text-neutral-400"
                    >
                </label>
                @error('exerciseQuery')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

                @if (count($groups) > 1)
                    <div class="mt-3 flex flex-wrap items-center gap-2 px-1 py-1">
                        <button type="button" wire:click="filterGroup('all')" @class([
                            'inline-flex h-8 shrink-0 items-center justify-center rounded-full px-3 text-sm font-semibold',
                            'bg-neutral-900 text-white' => $exerciseGroup === '',
                            'bg-neutral-100 text-neutral-600' => $exerciseGroup !== '',
                        ])>All</button>
                        @foreach ($groups as $group)
                            <button type="button" wire:click="filterGroup('{{ $group }}')" @class([
                                'inline-flex h-8 shrink-0 items-center justify-center rounded-full px-3 text-sm font-semibold',
                                'bg-neutral-900 text-white' => $exerciseGroup === $group,
                                'bg-neutral-100 text-neutral-600' => $exerciseGroup !== $group,
                            ])>{{ $group }}</button>
                        @endforeach
                    </div>
                @endif

                <ul class="mt-2 min-h-0 flex-1 overflow-y-auto px-1 py-1">
                    @if ($recentChoices->isNotEmpty())
                        <li class="px-1 pt-2 text-xs font-semibold uppercase tracking-wide text-neutral-400">Recent</li>
                        @foreach ($recentChoices as $choice)
                            @include('livewire.partials.exercise-choice', ['choice' => $choice])
                        @endforeach
                        <li class="px-1 pt-3 text-xs font-semibold uppercase tracking-wide text-neutral-400">All exercises</li>
                    @endif
                    @foreach ($exerciseChoices as $choice)
                        @include('livewire.partials.exercise-choice', ['choice' => $choice])
                    @endforeach
                </ul>

                @if ($canAddCustomExercise)
                    <button type="button" wire:click="addCustomExercise" class="mt-2 w-full shrink-0 rounded-2xl bg-neutral-100 py-2.5 text-sm font-semibold hover:bg-neutral-200">
                        Add “{{ trim($exerciseQuery) }}”
                    </button>
                @endif

                @if ($showsRunNote)
                    <p class="mt-3 text-sm text-neutral-500">Outdoor runs, treadmill runs, and walks are logged on <a href="{{ route('runs') }}" class="font-semibold text-[#2f6bff]">Runs</a>.</p>
                @endif
                <button type="button" wire:click="closeExercisePicker" class="mt-3 w-full shrink-0 rounded-2xl bg-[#2f6bff] py-3.5 text-base font-semibold text-white hover:bg-[#2458d6]">
                    Done
                </button>
            </section>
        </div>
    @endif

    @if ($canShare)
        <button
            type="button"
            wire:click="copyShare"
            class="flex w-full items-center justify-center gap-2 rounded-2xl bg-white py-3.5 text-base font-semibold text-neutral-900 shadow-sm ring-1 ring-neutral-300 hover:bg-neutral-50"
        >
            <span data-share-label>Share</span>
        </button>
    @endif
    <button
        type="button"
        wire:click="{{ $isComplete ? 'markIncomplete' : 'complete' }}"
        aria-pressed="{{ $isComplete ? 'true' : 'false' }}"
        @class([
            'flex w-full items-center justify-center gap-2 rounded-2xl py-3.5 text-base font-semibold shadow-sm',
            'bg-white text-neutral-900 ring-1 ring-neutral-300 hover:bg-neutral-50' => $isComplete,
            'bg-[#2f6bff] text-white hover:bg-[#2458d6]' => ! $isComplete,
        ])
    >
        {{ $isComplete ? 'Mark as Incomplete' : 'Complete Workout' }}
    </button>
    @error('complete')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
    @endif
</div>
