<div class="mx-auto flex w-full max-w-6xl flex-col gap-4 px-4 py-5 md:px-8 md:py-8">
    <header class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">One Rep Max</h1>
            <p class="mt-1 max-w-xl text-sm text-neutral-500">Bench, squat, and deadlift. Percentages and plates use the latest saved max.</p>
        </div>
        <button type="button" wire:click="toggleHowItWorks" class="inline-flex shrink-0 items-center gap-2 rounded-full border border-neutral-200 bg-white px-3 py-2 text-sm font-semibold shadow-sm">
            <x-app-icon name="info" class="size-4 text-neutral-500" />
            How it works
        </button>
    </header>

    @if ($exercises->isEmpty())
        <p class="rounded-3xl border border-dashed border-neutral-300 px-4 py-6 text-center text-sm text-neutral-500">These lifts are not available yet.</p>
    @else
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
            <div class="flex min-w-0 flex-1 flex-col gap-4">
                <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                        <label class="flex w-full flex-col gap-1 lg:max-w-xs">
                            <span class="text-[11px] font-semibold tracking-wide text-neutral-400">BAR</span>
                            <select wire:model.live="barWeight" aria-label="Bar size" class="rounded-xl border border-neutral-200 bg-white px-3 py-2 text-sm outline-none">
                                @foreach ($bars as $weight => $label)
                                    <option value="{{ $weight }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-semibold tracking-wide text-neutral-400">COMMON BARS (TOTAL WEIGHT)</p>
                            <div class="mt-1 flex flex-wrap gap-2">
                                @foreach ($commonBars as $weight => $label)
                                    <button
                                        type="button"
                                        wire:click="selectBar({{ $weight }})"
                                        class="rounded-full px-3 py-1.5 text-xs font-semibold {{ (int) $barWeight === $weight ? 'bg-[#2f6bff] text-white' : 'bg-neutral-100 text-neutral-700' }}"
                                    >{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                <div class="flex flex-col gap-3">
                    @foreach ($exercises as $exercise)
                        @if ($openId === $exercise->id)
                            <article wire:key="one-rep-max-{{ $exercise->id }}" class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-neutral-100 text-neutral-700">
                                            <x-app-icon name="dumbbell" class="size-5" />
                                        </span>
                                        <div class="min-w-0">
                                            <h2 class="text-base font-semibold">{{ $exercise->name }}</h2>
                                            @if ($exercise->group)
                                                <p class="text-xs text-neutral-500">{{ $exercise->group }}</p>
                                            @endif
                                            @if ($assessments[$exercise->id]['date'])
                                                <p class="mt-1 text-xs text-neutral-500">Assessed {{ $assessments[$exercise->id]['date'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <button type="button" wire:click="openNewMax({{ $exercise->id }})" class="inline-flex shrink-0 items-center gap-2 rounded-full bg-[#2f6bff] px-3 py-2 text-xs font-semibold text-white">
                                        Add a known max
                                    </button>
                                </div>

                                <div class="mt-4 rounded-2xl bg-[#e8f1ff] px-4 py-3">
                                    <p class="text-xs text-neutral-500">Last 1RM</p>
                                    <p class="text-3xl font-bold tracking-tight text-[#2f6bff]">
                                        {{ $assessments[$exercise->id]['weight'] ?: '—' }}
                                        @if ($assessments[$exercise->id]['weight'])
                                            <span class="text-base font-semibold">lbs</span>
                                        @endif
                                    </p>
                                    @if ($assessments[$exercise->id]['change'])
                                        <p class="text-xs font-semibold {{ $assessments[$exercise->id]['change']['up'] ? 'text-emerald-600' : 'text-red-600' }}">{{ $assessments[$exercise->id]['change']['text'] }} vs last</p>
                                    @else
                                        <p class="text-xs text-neutral-400">—</p>
                                    @endif
                                </div>

                                @if (($loadTables[$exercise->id]['rows'] ?? []) === [])
                                    <p class="mt-4 text-sm text-neutral-500">Add a known max to see the loads.</p>
                                @else
                                    <div class="mt-5 flex items-center gap-2">
                                        <h3 class="text-sm font-semibold">Percentage Based Weights</h3>
                                        <span class="text-neutral-400" title="Loads use the latest saved max.">
                                            <x-app-icon name="info" class="size-3.5" />
                                        </span>
                                    </div>
                                    <ul class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-9">
                                        @foreach ($loadTables[$exercise->id]['rows'] as $row)
                                            @if ($row['key'] !== 'custom')
                                                <li class="flex">
                                                    <button
                                                        type="button"
                                                        wire:click="selectPercent({{ $exercise->id }}, '{{ $row['key'] }}')"
                                                        class="flex h-16 w-full flex-col items-center justify-center rounded-xl border px-1 text-center whitespace-nowrap {{ ($loadTables[$exercise->id]['selected']['key'] ?? '') === $row['key'] ? 'border-[#2f6bff] bg-[#e8f1ff]' : 'border-neutral-200 bg-white' }}"
                                                    >
                                                        <span class="text-[11px] text-neutral-500">{{ $row['label'] }}</span>
                                                        <span class="text-sm font-semibold">{{ $row['target'] }} lbs</span>
                                                    </button>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>

                                    <div class="mt-4 grid grid-cols-2 items-end gap-3">
                                        <label class="min-w-0">
                                            <span class="text-[11px] font-semibold tracking-wide text-neutral-400">CUSTOM PERCENTAGE</span>
                                            <span class="mt-1 flex h-16 w-full items-center gap-2 rounded-xl border border-neutral-200 px-3">
                                                <input
                                                    type="text"
                                                    inputmode="decimal"
                                                    wire:model.live.blur="customPercents.{{ $exercise->id }}"
                                                    placeholder="65"
                                                    aria-label="Custom percent for {{ $exercise->name }}"
                                                    class="w-full text-base outline-none"
                                                >
                                                <span class="text-sm text-neutral-400">%</span>
                                            </span>
                                        </label>
                                        <p class="flex h-16 w-full min-w-0 items-center rounded-xl bg-[#e8f1ff] px-4 text-2xl font-bold text-[#2f6bff]">
                                            <span class="mr-2 text-sm font-semibold text-neutral-500">{{ $loadTables[$exercise->id]['selected']['label'] ?? '' }}</span>
                                            {{ $loadTables[$exercise->id]['selected']['target'] ?? '—' }}
                                            <span class="ml-1 text-sm font-semibold">lbs</span>
                                        </p>
                                    </div>
                                    @if ((int) $barWeight > 0 && $loadTables[$exercise->id]['selected'])
                                        <div class="mt-4">
                                            <h4 class="text-sm font-semibold">Plate Calculator <span class="font-normal text-neutral-500">(per side)</span></h4>
                                            <div class="mt-3 flex flex-wrap items-center gap-4">
                                                @if ($loadTables[$exercise->id]['selected']['plateCounts'] === [])
                                                    <span class="text-sm text-neutral-500">Bar only</span>
                                                @else
                                                    @foreach ($loadTables[$exercise->id]['selected']['plateCounts'] as $plate)
                                                        <x-weight-plate :size="$plate['size']" :count="$plate['count']" />
                                                    @endforeach
                                                @endif
                                                <div class="ml-auto border-l border-neutral-200 pl-4">
                                                    <p class="text-xs text-neutral-500">Total:</p>
                                                    <p class="text-xl font-bold tracking-tight">{{ $loadTables[$exercise->id]['selected']['loaded'] }} lbs</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @error('customPercents.'.$exercise->id)
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </article>
                        @else
                            <button type="button" wire:key="one-rep-max-{{ $exercise->id }}" wire:click="openExercise({{ $exercise->id }})" class="flex w-full items-center gap-3 rounded-2xl border border-neutral-200 bg-white px-3 py-3 text-left shadow-sm">
                                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-neutral-100 text-neutral-700">
                                    <x-app-icon name="dumbbell" class="size-5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold">{{ $exercise->name }}</span>
                                    @if ($exercise->group)
                                        <span class="block text-xs text-neutral-500">{{ $exercise->group }}</span>
                                    @endif
                                </span>
                                <span class="text-right">
                                    <span class="block text-[11px] text-neutral-400">Last 1RM</span>
                                    <span class="block text-sm font-semibold">{{ $assessments[$exercise->id]['weight'] ? $assessments[$exercise->id]['weight'].' lbs' : '—' }}</span>
                                </span>
                                @if ($assessments[$exercise->id]['change'])
                                    <span class="w-16 text-right text-xs font-semibold {{ $assessments[$exercise->id]['change']['up'] ? 'text-emerald-600' : 'text-red-600' }}">{{ $assessments[$exercise->id]['change']['text'] }}</span>
                                @else
                                    <span class="w-16 text-right text-xs text-neutral-400">—</span>
                                @endif
                                <x-app-icon name="chevron-down" class="size-4 text-neutral-400" />
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>

            <aside class="flex w-full shrink-0 flex-col gap-4 lg:w-72">
                <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold">Recent maxes</h2>
                        <button type="button" wire:click="toggleEstimates" class="text-xs font-semibold text-[#2f6bff]">{{ $showingAllEstimates ? 'Show latest' : 'View All' }}</button>
                    </div>
                    @if ($recent->isEmpty())
                        <p class="mt-3 text-sm text-neutral-500">Add a known max to see it here.</p>
                    @else
                        <ul class="mt-3 divide-y divide-neutral-100">
                            @foreach ($recent as $entry)
                                <li class="flex items-start justify-between gap-3 py-2">
                                    <span>
                                        <span class="block text-sm font-semibold">{{ $entry['name'] }}</span>
                                        <span class="block text-xs text-neutral-500">{{ $entry['date'] }}</span>
                                    </span>
                                    <span class="flex items-start gap-3 text-right">
                                        <span>
                                            <span class="block text-sm font-semibold">{{ $entry['weight'] }} lbs</span>
                                            @if ($entry['change'])
                                                <span class="block text-xs font-semibold {{ $entry['change']['up'] ? 'text-emerald-600' : 'text-red-600' }}">{{ $entry['change']['text'] }}</span>
                                            @else
                                                <span class="block text-xs text-neutral-400">—</span>
                                            @endif
                                        </span>
                                        <button type="button" wire:click="deleteMax({{ $entry['id'] }})" wire:confirm="Delete this max?" class="text-xs font-semibold text-red-600" aria-label="Delete {{ $entry['name'] }} max on {{ $entry['date'] }}">Delete</button>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                        @if ($showingAllEstimates && $recent->hasPages())
                            <nav class="mt-3 flex flex-wrap items-center justify-between gap-2" aria-label="Recent one rep maxes">
                                <button type="button" wire:click="previousPage" @disabled($recent->onFirstPage()) @class(['rounded-full px-3 py-1.5 text-xs font-semibold', 'bg-neutral-100 text-neutral-700' => ! $recent->onFirstPage(), 'text-neutral-400' => $recent->onFirstPage()])>Previous</button>
                                <span class="flex flex-wrap justify-center gap-1">
                                    @foreach ($recent->getUrlRange(max(1, $recent->currentPage() - 2), min($recent->lastPage(), $recent->currentPage() + 2)) as $page => $url)
                                        <button type="button" wire:click="gotoPage({{ $page }})" @class(['grid size-8 place-items-center rounded-full text-xs font-semibold', 'bg-[#2f6bff] text-white' => $page === $recent->currentPage(), 'text-neutral-600 hover:bg-neutral-100' => $page !== $recent->currentPage()])>{{ $page }}</button>
                                    @endforeach
                                </span>
                                <button type="button" wire:click="nextPage" @disabled(! $recent->hasMorePages()) @class(['rounded-full px-3 py-1.5 text-xs font-semibold', 'bg-neutral-100 text-neutral-700' => $recent->hasMorePages(), 'text-neutral-400' => ! $recent->hasMorePages()])>Next</button>
                            </nav>
                        @endif
                    @endif
                </section>
            </aside>
        </div>
    @endif

    @if ($addingExercise)
        <div
            class="fixed inset-0 z-50 flex items-end justify-center bg-neutral-950/40 p-4 sm:items-center"
            wire:click="closeNewMax"
            wire:keydown.escape.window="closeNewMax"
        >
            <section
                role="dialog"
                aria-modal="true"
                aria-labelledby="add-max-title"
                wire:click.stop
                class="w-full max-w-lg rounded-3xl bg-white p-4 shadow-xl"
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 id="add-max-title" class="text-lg font-bold tracking-tight">Add a known max</h2>
                        <p class="text-sm text-neutral-500">{{ $addingExercise->name }}</p>
                    </div>
                    <button type="button" wire:click="closeNewMax" class="grid size-10 place-items-center rounded-full bg-neutral-900 text-white hover:bg-neutral-700" aria-label="Close add max">
                        <x-app-icon name="close" class="size-5" />
                    </button>
                </div>
                <label class="mt-4 flex flex-col gap-1">
                    <span class="text-[11px] font-semibold tracking-wide text-neutral-400">DATE</span>
                    <input type="date" wire:model="newDate" aria-label="Assessment date" class="w-full rounded-xl border border-neutral-200 bg-white px-3 py-2 text-base outline-none focus:border-[#2f6bff]">
                </label>
                @error('newDate')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <label class="mt-3 flex flex-col gap-1">
                    <span class="text-[11px] font-semibold tracking-wide text-neutral-400">LBS</span>
                    <input type="text" inputmode="decimal" wire:model="newWeight" placeholder="Weight" aria-label="New one rep max" class="w-full rounded-xl border border-neutral-200 bg-white px-3 py-2 text-base outline-none focus:border-[#2f6bff]">
                </label>
                @error('newWeight')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <button type="button" wire:click="saveNewMax" class="mt-4 w-full rounded-2xl bg-[#2f6bff] py-3.5 text-base font-semibold text-white hover:bg-[#2458d6]">Save max</button>
            </section>
        </div>
    @endif

    @if ($showingHowItWorks)
        <div
            class="fixed inset-0 z-50 flex items-end justify-center bg-neutral-950/40 p-4 sm:items-center"
            wire:click="toggleHowItWorks"
            wire:keydown.escape.window="toggleHowItWorks"
        >
            <section
                role="dialog"
                aria-modal="true"
                aria-labelledby="how-it-works-title"
                wire:click.stop
                class="w-full max-w-lg rounded-3xl bg-white p-4 shadow-xl"
            >
                <div class="flex items-center justify-between gap-3">
                    <h2 id="how-it-works-title" class="text-lg font-bold tracking-tight">How it works</h2>
                    <button type="button" wire:click="toggleHowItWorks" class="grid size-10 place-items-center rounded-full bg-neutral-900 text-white hover:bg-neutral-700" aria-label="Close how it works">
                        <x-app-icon name="close" class="size-5" />
                    </button>
                </div>
                <p class="mt-3 text-sm text-neutral-600">Add a known max for bench, squat, or deadlift. The date is the day it was assessed, and earlier numbers stay on the record.</p>
                <p class="mt-3 text-sm text-neutral-600">Percentage loads and the plates per side are calculated from the latest saved max and the bar you pick.</p>
            </section>
        </div>
    @endif
</div>
