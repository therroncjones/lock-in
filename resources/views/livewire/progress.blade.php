<div class="flex w-full flex-col gap-4 px-4 py-5 md:px-8 md:py-8">
    <style>
        @media (width >= 48rem) {
            .workout-type-box {
                border-radius: 9999px;
            }
        }

        .plan-card-even,
        .plan-card-ahead {
            background: #f0fdf4;
        }

        .plan-card-under {
            background: #fef2f2;
        }

        .plan-icon-even,
        .plan-icon-ahead {
            background: #22c55e;
        }

        .plan-icon-under {
            background: #ef4444;
        }

        .plan-badge-even,
        .plan-badge-ahead {
            background: #dcfce7;
            color: #15803d;
        }

        .plan-badge-under {
            background: #fee2e2;
            color: #dc2626;
        }

        .plan-fill-even,
        .plan-fill-ahead {
            background: #22c55e;
        }

        .plan-fill-under {
            background: #ef4444;
        }

        .plan-value {
            width: 8.5rem;
        }
    </style>
    <header class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Progress</h1>
            <p class="mt-1 text-sm text-neutral-500">Track the work. See the results.</p>
        </div>
        <label class="flex items-center gap-2 rounded-xl border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm">
            <x-app-icon name="calendar" class="size-4 text-neutral-400" />
            <select wire:model.live="range" aria-label="Time range" class="bg-white text-sm outline-none">
                @foreach ($ranges as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>
    </header>

    <div class="flex flex-wrap items-center gap-3 rounded-2xl bg-white p-3 shadow-sm ring-1 ring-neutral-200/70">
            <div class="workout-type-box flex flex-wrap gap-1 rounded-3xl bg-neutral-100 p-1">
                @foreach ($types as $option)
                    <button
                        type="button"
                        wire:click="filterType('{{ $option }}')"
                        @class([
                            'rounded-full px-3 py-1.5 text-sm font-semibold',
                            'bg-[#1e3a5f] text-white' => $type === $option,
                            'text-neutral-600' => $type !== $option,
                        ])
                    >{{ $option }}</button>
                @endforeach
            </div>
            @if ($type === 'Strength - Full Body')
                <p class="w-full text-xs text-neutral-500">Includes upper body, lower body, and full body sessions.</p>
            @endif
            @unless ($choices->isEmpty())
                <label class="min-w-0 flex-1 md:max-w-xs md:flex-none">
                    <select wire:model.live="exercise" aria-label="Exercise" class="w-full rounded-xl border border-neutral-200 bg-white px-3 py-2.5 text-sm">
                        @foreach ($choices as $choice)
                            <option value="{{ $choice['id'] }}">{{ $choice['name'] }}</option>
                        @endforeach
                    </select>
                </label>
            @endunless
        </div>

        @if ($runs)
            <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold">Runs</h2>
                        <p class="mt-1 text-xs text-neutral-500">Pace and splits from the Runs log.</p>
                    </div>
                    <a href="{{ route('runs') }}" class="text-sm font-semibold text-[#2f6bff]">Open Runs</a>
                </div>
                <p class="mt-3 text-2xl font-bold">{{ $runs['count'] === 1 ? '1 run' : $runs['count'].' runs' }}</p>
                <p class="mt-1 text-sm text-neutral-500">{{ $runs['miles'] }} mi · {{ $runs['pace'] }} /mi</p>
                <ul class="mt-3 divide-y divide-neutral-100">
                    @foreach ($runs['runs'] as $run)
                        <li class="py-3">
                            <a href="{{ $run['href'] }}" class="flex items-start justify-between gap-3">
                                <span>
                                    <span class="block text-sm font-semibold">{{ $run['label'] }}</span>
                                    <span class="block text-xs text-neutral-500">{{ $run['date'] }}</span>
                                </span>
                                <span class="text-right">
                                    <span class="block text-sm font-semibold">{{ $run['distance'] }} mi</span>
                                    <span class="block text-xs text-neutral-500">{{ $run['pace'] }}</span>
                                </span>
                            </a>
                            @if ($run['splits'] !== [])
                                <p class="mt-1 text-xs text-neutral-500">{{ implode(' · ', $run['splits']) }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
        @if ($choices->isEmpty() && ! $runs)
            <p class="rounded-3xl border border-dashed border-neutral-300 px-4 py-6 text-center text-sm text-neutral-500">Log a workout to see progress.</p>
        @elseif ($summary)
            <section class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                @if ($summary['oneRepMax'])
                    <article class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="grid size-8 place-items-center rounded-full bg-[#e8f0ff] text-[#2f6bff]">
                                    <x-app-icon name="dumbbell" class="size-4" />
                                </span>
                                <p class="text-xs text-neutral-500">1 Rep Max</p>
                            </div>
                            <p class="mt-1 text-[11px] leading-snug text-neutral-400">Latest max saved for this lift.</p>
                            <p class="mt-2 text-2xl font-bold">{{ $summary['oneRepMax']['weight'] !== null ? $summary['oneRepMax']['weight'].' lbs' : '—' }}</p>
                            @if ($summary['oneRepMax']['changeLbs'] !== null)
                                <p @class([
                                    'mt-1 text-xs font-semibold',
                                    'text-emerald-500' => $summary['oneRepMax']['changeLbs'] >= 0,
                                    'text-red-600' => $summary['oneRepMax']['changeLbs'] < 0,
                                ])>{{ $summary['oneRepMax']['changeLbs'] >= 0 ? '↑ +'.$summary['oneRepMax']['changeLbs'].' lbs' : '↓ '.$summary['oneRepMax']['changeLbs'].' lbs' }} vs last best</p>
                            @endif
                            @if ($summary['oneRepMax']['date'])
                                <p class="mt-1 text-xs text-neutral-500">{{ $summary['oneRepMax']['date'] }}</p>
                            @endif
                        </div>
                        @include('livewire.partials.progress-spark', ['bars' => $summary['maxBars'], 'color' => '#93c5fd'])
                    </article>
                @endif
                <article class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="grid size-8 place-items-center rounded-full bg-amber-100 text-amber-500">
                                <x-app-icon name="medal" class="size-4" />
                            </span>
                            <p class="text-xs text-neutral-500">Best Set</p>
                        </div>
                        <p class="mt-1 text-[11px] leading-snug text-neutral-400">{{ $summary['bestCaption'] }}</p>
                        <p class="mt-2 text-2xl font-bold">{{ $summary['bestSet']['label'] }}</p>
                        <p class="mt-1 text-xs text-neutral-500">{{ $summary['bestSet']['date'] }}</p>
                    </div>
                </article>
                <article class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="grid size-8 place-items-center rounded-full bg-emerald-100 text-emerald-600">
                                <x-app-icon name="volume" class="size-4" />
                            </span>
                            <p class="text-xs text-neutral-500">Total Volume</p>
                        </div>
                        <p class="mt-1 text-[11px] leading-snug text-neutral-400">{{ $summary['volumeCaption'] }}</p>
                        <p class="mt-2 text-2xl font-bold">{{ $summary['volumeText'] }}</p>
                        @if ($summary['volumeChange'] !== null)
                            <p @class([
                                'mt-1 text-xs font-semibold',
                                'text-emerald-500' => $summary['volumeChange'] >= 0,
                                'text-red-600' => $summary['volumeChange'] < 0,
                            ])>{{ $summary['volumeChange'] >= 0 ? '↑ +'.$summary['volumeChange'].'%' : '↓ '.$summary['volumeChange'].'%' }} {{ $summary['volumeChangeLabel'] }}</p>
                        @else
                            <p class="mt-1 text-xs text-neutral-500">{{ $rangeLabel }}</p>
                        @endif
                    </div>
                    @include('livewire.partials.progress-spark', ['bars' => $summary['volumeBars'], 'color' => '#6ee7b7'])
                </article>
                <article class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="grid size-8 place-items-center rounded-full bg-violet-100 text-violet-500">
                                <x-app-icon name="calendar" class="size-4" />
                            </span>
                            <p class="text-xs text-neutral-500">Sessions</p>
                        </div>
                        <p class="mt-1 text-[11px] leading-snug text-neutral-400">Workouts that included this exercise.</p>
                        <p class="mt-2 text-2xl font-bold">{{ $summary['sessions'] }}</p>
                        <p class="mt-1 text-xs text-neutral-500">{{ $summary['sessions'] === 1 ? '1 session' : $summary['sessions'].' sessions' }}</p>
                        @if ($summary['sessionChange'])
                            <p @class([
                                'mt-1 text-xs font-semibold',
                                'text-emerald-500' => $summary['sessionChange']['change'] >= 0,
                                'text-red-600' => $summary['sessionChange']['change'] < 0,
                            ])>{{ $summary['sessionChange']['change'] >= 0 ? '↑ +'.$summary['sessionChange']['change'] : '↓ '.$summary['sessionChange']['change'] }} {{ $summary['sessionChange']['label'] }}</p>
                        @endif
                        <p class="mt-1 text-xs text-neutral-500">Last {{ $summary['lastPerformed'] }}</p>
                    </div>
                    @include('livewire.partials.progress-spark', ['bars' => $sessionBars, 'color' => '#c4b5fd'])
                </article>
                @if ($summary['intensity'])
                    <article class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="grid size-8 place-items-center rounded-full bg-[#e8f0ff] text-[#2f6bff]">
                                    <x-app-icon name="target" class="size-4" />
                                </span>
                                <p class="text-xs text-neutral-500">% of 1RM</p>
                            </div>
                            <p class="mt-1 text-[11px] leading-snug text-neutral-400">Best set compared with your max that day.</p>
                            <p class="mt-2 text-2xl font-bold">{{ $summary['intensity']['percent'] }}%</p>
                            <p class="mt-1 text-xs text-neutral-500">{{ $summary['intensity']['detail'] }}</p>
                        </div>
                        <svg viewBox="0 0 36 36" class="size-14 shrink-0" aria-hidden="true">
                            <circle cx="18" cy="18" r="14" fill="none" stroke="#e5e7eb" stroke-width="4" />
                            <circle
                                cx="18"
                                cy="18"
                                r="14"
                                fill="none"
                                stroke="#2f6bff"
                                stroke-width="4"
                                stroke-linecap="round"
                                stroke-dasharray="{{ min($summary['intensity']['percent'], 100) }} 100"
                                pathLength="100"
                                transform="rotate(-90 18 18)"
                            />
                        </svg>
                    </article>
                @endif
            </section>

            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
                <div class="flex items-center gap-3">
                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-[#e8f0ff] text-[#2f6bff]">
                        <x-app-icon name="progress" class="size-4" />
                    </span>
                    <div>
                        <h2 class="font-semibold">Planned vs Actual</h2>
                        <p class="text-xs text-neutral-500">Each set compared with what was planned.</p>
                    </div>
                </div>
                @if ($summary['actuals'] !== [])
                    <div class="mt-4 flex flex-col gap-3 overflow-y-auto" style="max-height: 36rem">
                        @foreach ($summary['actuals'] as $actual)
                            <div @class(['plan-card rounded-2xl p-4', 'plan-card-'.$actual['tone']])>
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span @class(['plan-icon grid size-8 shrink-0 place-items-center rounded-full text-white', 'plan-icon-'.$actual['tone']])>
                                            @if ($actual['tone'] === 'under')
                                                <x-app-icon name="alert" class="size-4" />
                                            @else
                                                <x-app-icon name="check" class="size-4" />
                                            @endif
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-bold leading-tight">{{ $actual['status'] }}</p>
                                            <p class="mt-1 text-xs text-neutral-500">Plan {{ $actual['planned'] }}</p>
                                        </div>
                                    </div>
                                    <span @class(['plan-badge shrink-0 rounded-full px-2 py-1 text-[11px] font-semibold tracking-wide', 'plan-badge-'.$actual['tone']])>{{ $actual['badge'] }}</span>
                                </div>
                                <div class="mt-4 flex items-center gap-4">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs text-neutral-500">Planned</p>
                                        <div class="mt-1 flex items-center gap-3">
                                            <p class="plan-value shrink-0 whitespace-nowrap text-sm font-bold">{{ $actual['plannedBar'] }}</p>
                                            <div class="h-2 min-w-0 flex-1 rounded-full bg-neutral-200"></div>
                                        </div>
                                        <p class="mt-3 text-xs text-neutral-500">Actual</p>
                                        <div class="mt-1 flex items-center gap-3">
                                            <p class="plan-value shrink-0 whitespace-nowrap text-sm font-bold">{{ $actual['actualBar'] }}</p>
                                            <div class="h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-neutral-100">
                                                <div @class(['plan-fill h-2 rounded-full', 'plan-fill-'.$actual['tone']]) style="width: {{ $actual['bar'] }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="shrink-0 border-l border-neutral-200 pl-4 text-right" style="width: 6.75rem">
                                        <p class="text-xs text-neutral-500">Difference</p>
                                        @if ($actual['weightDelta'] !== null)
                                            <p @class([
                                                'mt-1 text-sm font-bold',
                                                'text-emerald-600' => $actual['weightDirection'] > 0,
                                                'text-red-600' => $actual['weightDirection'] < 0,
                                            ])>{{ $actual['weightDelta'] }}</p>
                                        @endif
                                        @if ($actual['amountDelta'] !== null)
                                            <p @class([
                                                'text-xs',
                                                'mt-1 font-bold' => $actual['weightDelta'] === null,
                                                'font-semibold' => $actual['weightDelta'] !== null && $actual['amountDirection'] !== 0,
                                                'text-emerald-600' => $actual['amountDirection'] > 0,
                                                'text-red-600' => $actual['amountDirection'] < 0,
                                                'text-neutral-400' => $actual['amountDirection'] === 0 && $actual['weightDelta'] !== null,
                                            ])>{{ $actual['amountDelta'] }}</p>
                                        @endif
                                        @if ($actual['weightDelta'] === null && $actual['amountDelta'] === null)
                                            <p class="mt-1 text-sm font-bold">{{ $actual['delta'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 text-2xl font-bold">—</p>
                    <p class="mt-1 text-xs text-neutral-500">No actuals logged</p>
                @endif
            </article>

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                @if ($summary['chart'])
                    <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
                        <h2 class="font-semibold">1RM Progress</h2>
                        @include('livewire.partials.progress-chart', [
                            'label' => 'One rep max over time',
                            'chart' => $summary['chart'],
                        ])
                    </article>
                @endif
                @if ($summary['heaviestChart'])
                    <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
                        <h2 class="font-semibold">{{ $summary['chartHeading'] }}</h2>
                        @include('livewire.partials.progress-chart', [
                            'label' => $summary['chartHeading'].' over time',
                            'chart' => $summary['heaviestChart'],
                        ])
                    </article>
                @endif
            </section>

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold">Recent Sets</h2>
                        @if ($summary['hasMoreSets'])
                            <button type="button" wire:click="{{ $showingEverySet ? 'showRecentSets' : 'showEverySet' }}" class="text-sm font-semibold text-[#2f6bff]">
                                {{ $showingEverySet ? 'Show recent' : 'View All' }}
                            </button>
                        @endif
                    </div>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-neutral-200 text-xs text-neutral-500">
                                    <th class="py-2 font-medium">Date</th>
                                    <th class="py-2 font-medium">{{ $summary['setHeading'] }}</th>
                                    @if ($summary['measure'] === 'reps')
                                        <th class="py-2 font-medium">Volume</th>
                                        <th class="py-2 font-medium">% 1RM</th>
                                    @endif
                                    <th class="py-2 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($summary['sets'] as $set)
                                    <tr wire:key="progress-set-{{ $set['id'] }}" class="border-b border-neutral-100">
                                        <td class="py-3 text-neutral-600">{{ $set['date'] }}</td>
                                        <td class="py-3 font-semibold">{{ $set['label'] }}</td>
                                        @if ($summary['measure'] === 'reps')
                                            <td class="py-3 text-neutral-600">{{ $set['volume'] }}</td>
                                            <td class="py-3 text-neutral-600">{{ $set['percent'] !== null ? $set['percent'].'%' : '—' }}</td>
                                        @endif
                                        <td class="py-3 text-right">
                                            @if ($set['isPr'])
                                                <span class="rounded-full bg-emerald-500 px-2 py-1 text-[11px] font-semibold text-white">PR</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($summary['setPages']?->hasPages())
                        <nav class="mt-4 flex flex-wrap items-center justify-between gap-3" aria-label="Recent sets pages">
                            <button type="button" wire:click="previousPage" @disabled($summary['setPages']->onFirstPage()) @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-neutral-100 text-neutral-700' => ! $summary['setPages']->onFirstPage(), 'text-neutral-400' => $summary['setPages']->onFirstPage()])>Previous</button>
                            <div class="flex flex-wrap items-center justify-center gap-1">
                                @foreach ($summary['setPages']->getUrlRange(max(1, $summary['setPages']->currentPage() - 2), min($summary['setPages']->lastPage(), $summary['setPages']->currentPage() + 2)) as $page => $url)
                                    <button type="button" wire:click="gotoPage({{ $page }})" @class(['grid size-8 place-items-center rounded-full text-sm font-semibold', 'bg-[#2f6bff] text-white' => $page === $summary['setPages']->currentPage(), 'text-neutral-600 hover:bg-neutral-100' => $page !== $summary['setPages']->currentPage()])>{{ $page }}</button>
                                @endforeach
                            </div>
                            <button type="button" wire:click="nextPage" @disabled(! $summary['setPages']->hasMorePages()) @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-neutral-100 text-neutral-700' => $summary['setPages']->hasMorePages(), 'text-neutral-400' => ! $summary['setPages']->hasMorePages()])>Next</button>
                        </nav>
                    @endif
                </article>
                @if ($weekChart)
                    <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
                        <h2 class="font-semibold">Workouts per week</h2>
                        <svg viewBox="0 0 {{ $weekChart['width'] }} {{ $weekChart['height'] }}" class="mt-3 w-full" role="img" aria-label="Completed workouts per week">
                            @foreach ($weekChart['ticks'] as $tick)
                                <line x1="{{ $weekChart['left'] }}" x2="{{ $weekChart['width'] - $weekChart['right'] }}" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}" stroke="#e5e7eb" />
                                <text x="0" y="{{ $tick['y'] + 4 }}" fill="#a3a3a3" font-size="8">{{ $tick['label'] }}</text>
                            @endforeach
                            @foreach ($weekChart['bars'] as $bar)
                                @if ($bar['height'] > 0)
                                    <rect x="{{ $bar['x'] }}" y="{{ $bar['y'] }}" width="{{ $bar['width'] }}" height="{{ $bar['height'] }}" rx="2" fill="#3b82f6">
                                        <title>{{ $bar['label'] }}</title>
                                    </rect>
                                @endif
                            @endforeach
                            @foreach ($weekChart['labels'] as $month)
                                <text x="{{ $month['x'] }}" y="{{ $weekChart['height'] - 4 }}" fill="#a3a3a3" font-size="8" text-anchor="middle">{{ $month['text'] }}</text>
                            @endforeach
                        </svg>
                    </article>
                @endif
            </section>
        @elseif ($choices->isNotEmpty())
            <p class="rounded-3xl border border-dashed border-neutral-300 px-4 py-6 text-center text-sm text-neutral-500">No sets in this range.</p>
        @endif
</div>
