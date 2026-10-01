@php $isOpen = $expanded[$entry->id] ?? false; @endphp
<article wire:key="exercise-{{ $entry->id }}" class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
    <div class="flex items-center gap-3">
        <span class="grid size-8 shrink-0 place-items-center rounded-xl bg-neutral-100 text-sm font-semibold text-neutral-700">{{ $entry->position }}</span>
        <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-neutral-900 text-white">
            <x-app-icon name="dumbbell" class="size-6" />
        </span>
        <div class="min-w-0 flex-1">
            @if ($entry->exercise->approved_at === null && $entry->exercise->user_id === auth()->id() && ! $isComplete)
                <input
                    type="text"
                    wire:model.blur="pendingNames.{{ $entry->exercise->id }}"
                    maxlength="100"
                    aria-label="Exercise name"
                    class="w-full truncate bg-transparent font-semibold outline-none"
                >
                @error('pendingNames.'.$entry->exercise->id)
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            @else
                <h3 class="truncate font-semibold">{{ $entry->exercise->name }}</h3>
            @endif
            <p class="truncate text-sm text-neutral-500">
                @if ($entry->exercise->approved_at === null)
                    Pending approval
                @else
                    {{ $entry->exercise->group ?? 'Custom' }}
                @endif
                · {{ $entry->sets->count() }} {{ $entry->sets->count() === 1 ? 'set' : 'sets' }}
            </p>
        </div>
        <button type="button" wire:click="toggleExercise({{ $entry->id }})" class="grid size-8 place-items-center text-neutral-400 hover:text-neutral-700" aria-label="{{ $isOpen ? 'Collapse' : 'Expand' }} {{ $entry->exercise->name }}">
            <x-app-icon :name="$isOpen ? 'chevron-up' : 'chevron-down'" class="size-5" />
        </button>
    </div>

    @if ($isOpen)
        <div class="mt-4 flex flex-col">
            @foreach ($entry->sets as $set)
                <div
                    wire:key="set-{{ $set->id }}"
                    @class([
                        'flex flex-col gap-2',
                        'border-t border-neutral-100' => ! $loop->first,
                    ])
                    @unless ($loop->first) style="margin-top: 0.75rem; padding-top: 0.75rem" @endunless
                >
                <div class="grid grid-cols-[1.75rem_minmax(0,1fr)_minmax(0,1fr)_2rem_2rem] items-end gap-2">
                    <span class="mb-2 text-sm font-semibold text-neutral-500">{{ $set->position }}</span>
                    <label class="flex min-w-0 flex-col gap-1">
                        <span class="whitespace-nowrap text-center text-[11px] font-semibold text-neutral-400">Planned Weight (lbs)</span>
                        <input
                            type="text"
                            inputmode="decimal"
                            wire:model.blur="setWeights.{{ $set->id }}"
                            placeholder="Weight"
                            aria-label="Weight for set {{ $set->position }}"
                            @readonly($isComplete)
                            @class([
                                'w-full rounded-xl border border-neutral-200 px-2 py-2 text-center text-sm outline-none focus:border-[#2f6bff]',
                                'bg-neutral-50' => $isComplete,
                                'bg-white' => ! $isComplete,
                            ])
                        >
                        @error('setWeights.'.$set->id)
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </label>
                    <label class="flex min-w-0 flex-col gap-1">
                        <span class="whitespace-nowrap text-center text-[11px] font-semibold text-neutral-400">Planned Reps</span>
                        <input
                            type="text"
                            inputmode="numeric"
                            wire:model.blur="setReps.{{ $set->id }}"
                            placeholder="Reps"
                            aria-label="Reps for set {{ $set->position }}"
                            @readonly($isComplete)
                            @class([
                                'w-full rounded-xl border border-neutral-200 px-2 py-2 text-center text-sm outline-none focus:border-[#2f6bff]',
                                'bg-neutral-50' => $isComplete,
                                'bg-white' => ! $isComplete,
                            ])
                        >
                        @error('setReps.'.$set->id)
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </label>
                    @if ($isComplete)
                        <span></span>
                    @endif
                    <button
                        type="button"
                        wire:click="toggleSetCompleted({{ $set->id }})"
                        @disabled($isComplete)
                        aria-label="{{ $set->completed ? 'Mark set '.$set->position.' incomplete' : 'Mark set '.$set->position.' complete' }}"
                        aria-pressed="{{ $set->completed ? 'true' : 'false' }}"
                        @class([
                            'mb-2 grid size-7 place-items-center rounded-full',
                            'ml-auto' => $isComplete,
                            'bg-emerald-500 text-white' => $set->completed,
                            'bg-white text-neutral-300 ring-1 ring-neutral-300' => ! $set->completed,
                        ])
                    >
                        @if ($set->completed)
                            <x-app-icon name="check" class="size-3.5" />
                        @endif
                    </button>
                    @unless ($isComplete)
                        <button type="button" wire:click="removeSet({{ $set->id }})" class="mb-2 grid size-7 place-items-center text-neutral-400 hover:text-neutral-700" aria-label="Remove set {{ $set->position }}">
                            <x-app-icon name="trash" class="size-4" />
                        </button>
                    @endunless
                </div>
                <div class="grid grid-cols-[1.75rem_minmax(0,1fr)_minmax(0,1fr)_2rem_2rem] gap-2">
                    <span></span>
                    <label class="flex min-w-0 flex-col gap-1">
                        <span class="text-center text-[11px] font-semibold tracking-wide text-neutral-400">Actual Weight</span>
                        <input
                            type="text"
                            inputmode="decimal"
                            wire:model.blur="setActualWeights.{{ $set->id }}"
                            placeholder="Weight"
                            aria-label="Actual weight for set {{ $set->position }}"
                            @readonly($isComplete)
                            @class([
                                'w-full rounded-xl border border-neutral-200 px-2 py-2 text-center text-sm outline-none focus:border-[#2f6bff]',
                                'bg-neutral-50' => $isComplete,
                                'bg-white' => ! $isComplete,
                            ])
                        >
                        @error('setActualWeights.'.$set->id)
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </label>
                    <label class="flex min-w-0 flex-col gap-1">
                        <span class="text-center text-[11px] font-semibold tracking-wide text-neutral-400">Actual Reps</span>
                        <input
                            type="text"
                            inputmode="numeric"
                            wire:model.blur="setActualReps.{{ $set->id }}"
                            placeholder="Reps"
                            aria-label="Actual reps for set {{ $set->position }}"
                            @readonly($isComplete)
                            @class([
                                'w-full rounded-xl border border-neutral-200 px-2 py-2 text-center text-sm outline-none focus:border-[#2f6bff]',
                                'bg-neutral-50' => $isComplete,
                                'bg-white' => ! $isComplete,
                            ])
                        >
                        @error('setActualReps.'.$set->id)
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </label>
                </div>
                </div>
            @endforeach
        </div>

        @unless ($isComplete)
            <button type="button" wire:click="addSet({{ $entry->id }})" class="mt-4 flex w-full items-center justify-center gap-1 rounded-2xl bg-[#e8f0ff] py-2.5 text-sm font-semibold text-[#2f6bff] hover:bg-[#dce8ff]">
                <x-app-icon name="log" class="size-4" />
                Add Set
            </button>
            <div class="mt-2 flex items-center justify-between gap-3">
                <button type="button" wire:click="removeExercise({{ $entry->id }})" class="py-1 text-sm text-neutral-400 hover:text-neutral-700">
                    Remove exercise
                </button>
                @if ($blocks->isNotEmpty())
                    <select
                        wire:change="assignExercise({{ $entry->id }}, $event.target.value)"
                        aria-label="Block for {{ $entry->exercise->name }}"
                        class="rounded-xl border border-neutral-200 bg-white px-3 py-1.5 text-sm outline-none focus:border-[#2f6bff]"
                    >
                        <option value="" @selected($entry->workout_block_id === null)>No block</option>
                        @foreach ($blocks as $blockOption)
                            <option value="{{ $blockOption->id }}" @selected($entry->workout_block_id === $blockOption->id)>{{ $blockOption->name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        @endunless
    @endif
</article>
