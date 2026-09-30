<div class="flex w-full flex-col gap-4 px-4 py-5 md:px-8 md:py-8">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Runs</h1>
            <p class="mt-1 text-sm text-neutral-500">Track your runs. Keep building.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="startLog" class="inline-flex items-center gap-2 rounded-xl bg-[#2f6bff] px-4 py-2 text-sm font-semibold text-white">
                <x-app-icon name="run" class="size-4" />
                Log Run
            </button>
            <label class="flex items-center gap-2 rounded-xl border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm">
                <x-app-icon name="calendar" class="size-4 text-neutral-400" />
                <select wire:model.live="range" aria-label="Time range" class="bg-white text-sm outline-none">
                    @foreach ($ranges as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </header>

    <section class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        @include('livewire.partials.run-stat', ['label' => 'Total Distance', 'value' => $summary['distance'], 'change' => $summary['distanceChange'], 'changeLabel' => $summary['changeLabel'], 'icon' => 'run', 'iconClass' => 'bg-[#e8f0ff] text-[#2f6bff]', 'bars' => $summary['distanceBars'], 'color' => '#93c5fd', 'lowerIsBetter' => false])
        @include('livewire.partials.run-stat', ['label' => 'Total Time', 'value' => $summary['time'], 'change' => $summary['timeChange'], 'changeLabel' => $summary['changeLabel'], 'icon' => 'clock', 'iconClass' => 'bg-[#e8f0ff] text-[#2f6bff]', 'bars' => $summary['timeBars'], 'color' => '#93c5fd', 'lowerIsBetter' => false])
        @include('livewire.partials.run-stat', ['label' => 'Avg Pace', 'value' => $summary['pace'], 'change' => $summary['paceChange'], 'changeLabel' => $summary['changeLabel'], 'icon' => 'timer', 'iconClass' => 'bg-[#e8f0ff] text-[#2f6bff]', 'bars' => $summary['paceBars'], 'color' => '#93c5fd', 'lowerIsBetter' => true])
        @include('livewire.partials.run-stat', ['label' => 'Total Calories', 'value' => $summary['calories'], 'change' => $summary['calorieChange'], 'changeLabel' => $summary['changeLabel'], 'icon' => 'fire', 'iconClass' => 'bg-orange-100 text-orange-500', 'bars' => $summary['calorieBars'], 'color' => '#fdba74', 'lowerIsBetter' => false])
    </section>

    <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-semibold">Run History</h2>
                <label class="flex min-w-0 flex-1 items-center gap-2 rounded-xl border border-neutral-200 px-3 py-2 md:max-w-xs md:flex-none">
                    <x-app-icon name="search" class="size-4 text-neutral-400" />
                    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search runs..." aria-label="Search runs" class="w-full bg-white text-sm outline-none">
                </label>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <button type="button" wire:click="$set('activity', 'all')" @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-[#2f6bff] text-white' => $activity === 'all', 'bg-neutral-100 text-neutral-600' => $activity !== 'all'])>All</button>
                @foreach ($types as $value => $label)
                    <button type="button" wire:click="$set('activity', '{{ $value }}')" @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-[#2f6bff] text-white' => $activity === $value, 'bg-neutral-100 text-neutral-600' => $activity !== $value])>{{ $value === 'outdoor' ? 'Outdoor' : ($value === 'treadmill' ? 'Treadmill' : $label) }}</button>
                @endforeach
                <label class="md:ml-auto">
                    <select wire:model.live="sort" aria-label="Sort runs" class="rounded-xl border border-neutral-200 bg-white px-3 py-2 text-sm">
                        @foreach ($sorts as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            @if ($runs->isEmpty())
                <p class="mt-6 rounded-3xl border border-dashed border-neutral-300 px-4 py-6 text-center text-sm text-neutral-500">No runs in this range.</p>
            @else
                <div class="mt-2 divide-y divide-neutral-100">
                    @foreach ($runs as $entry)
                        <button type="button" wire:click="selectRun({{ $entry->id }})" wire:key="run-{{ $entry->id }}" @class(['flex w-full items-center gap-3 py-3 text-left', 'rounded-xl bg-neutral-50 px-2' => $selected && $selected->id === $entry->id])>
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-[#e8f0ff] text-[#2f6bff]">
                                <x-app-icon name="{{ $entry->type === 'walk' ? 'mobility' : 'run' }}" class="size-4" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold">{{ $entry->performed_on->format('D, M j, Y') }}</span>
                                <span class="block text-xs text-neutral-500">{{ $entry->startedAtLabel() ? $entry->startedAtLabel().' · ' : '' }}{{ $entry->typeLabel() }}</span>
                            </span>
                            <span class="hidden text-right sm:block">
                                <span class="block text-sm font-semibold">{{ $entry->distanceLabel() }} mi</span>
                                <span class="block text-xs text-neutral-500">Distance</span>
                            </span>
                            <span class="hidden text-right sm:block">
                                <span class="block text-sm font-semibold">{{ $entry->durationLabel() }}</span>
                                <span class="block text-xs text-neutral-500">Time</span>
                            </span>
                            <span class="text-right">
                                <span class="block text-sm font-semibold">{{ $entry->paceLabel() }}</span>
                                <span class="block text-xs text-neutral-500">Pace</span>
                            </span>
                            @if ($entry->elevation_gain_feet)
                                <span class="hidden text-xs font-semibold text-neutral-500 lg:block">↑ {{ $entry->elevation_gain_feet }} ft</span>
                            @endif
                            <x-app-icon name="chevron-right" class="size-3 text-neutral-300" />
                        </button>
                    @endforeach
                </div>
                @if ($runs->hasPages())
                    <nav class="mt-4 flex flex-wrap items-center justify-between gap-3" aria-label="Run history pages">
                        <button type="button" wire:click="previousPage" @disabled($runs->onFirstPage()) @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-neutral-100 text-neutral-700' => ! $runs->onFirstPage(), 'text-neutral-400' => $runs->onFirstPage()])>Previous</button>
                        <div class="flex flex-wrap items-center justify-center gap-1">
                            @foreach ($runs->getUrlRange(max(1, $runs->currentPage() - 2), min($runs->lastPage(), $runs->currentPage() + 2)) as $page => $url)
                                <button type="button" wire:click="gotoPage({{ $page }})" @class(['grid size-8 place-items-center rounded-full text-sm font-semibold', 'bg-[#2f6bff] text-white' => $page === $runs->currentPage(), 'text-neutral-600 hover:bg-neutral-100' => $page !== $runs->currentPage()])>{{ $page }}</button>
                            @endforeach
                        </div>
                        <button type="button" wire:click="nextPage" @disabled(! $runs->hasMorePages()) @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-neutral-100 text-neutral-700' => $runs->hasMorePages(), 'text-neutral-400' => ! $runs->hasMorePages()])>Next</button>
                    </nav>
                @endif
            @endif
        </article>

        @if ($selected)
            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">{{ $selected->performed_on->format('D, M j, Y') }}</h2>
                        <p class="mt-1 text-sm text-neutral-500">{{ $selected->startedAtLabel() ? $selected->startedAtLabel().' · ' : '' }}{{ $selected->typeLabel() }}</p>
                    </div>
                    <button type="button" wire:click="editRun({{ $selected->id }})" class="rounded-xl border border-neutral-200 px-3 py-2 text-sm font-semibold">Edit</button>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div>
                        <p class="text-xl font-bold">{{ $selected->distanceLabel() }}</p>
                        <p class="text-xs text-neutral-500">Distance (mi)</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold">{{ $selected->durationLabel() }}</p>
                        <p class="text-xs text-neutral-500">Time</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold">{{ $selected->paceLabel() }}</p>
                        <p class="text-xs text-neutral-500">Avg Pace (min/mi)</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold">{{ $selected->calories === null ? '—' : number_format($selected->calories) }}</p>
                        <p class="text-xs text-neutral-500">Calories</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold">{{ $selected->average_heart_rate ?? '—' }}</p>
                        <p class="text-xs text-neutral-500">Avg HR (bpm)</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold">{{ $selected->max_heart_rate ?? '—' }}</p>
                        <p class="text-xs text-neutral-500">Max HR</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold">{{ $selected->elevation_gain_feet === null ? '—' : $selected->elevation_gain_feet.' ft' }}</p>
                        <p class="text-xs text-neutral-500">Elevation Gain</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold">{{ $selected->temperature_fahrenheit === null ? '—' : $selected->temperature_fahrenheit.'°F' }}</p>
                        <p class="text-xs text-neutral-500">Temperature</p>
                    </div>
                </div>

                <div class="mt-5 flex gap-4 border-b border-neutral-200 text-sm font-semibold">
                    @foreach (['splits' => 'Splits', 'elevation' => 'Elevation', 'heart' => 'Heart Rate'] as $tab => $label)
                        <button type="button" wire:click="showDetail('{{ $tab }}')" @class(['border-b-2 pb-2', 'border-[#2f6bff] text-[#2f6bff]' => $detailTab === $tab, 'border-transparent text-neutral-500' => $detailTab !== $tab])>{{ $label }}</button>
                    @endforeach
                </div>

                @if ($detailTab === 'splits')
                    @if ($selected->splits->isEmpty())
                        <p class="py-6 text-sm text-neutral-500">No splits logged.</p>
                    @else
                        <table class="mt-3 w-full text-left text-sm">
                            <thead>
                                <tr class="text-xs text-neutral-500">
                                    <th class="py-2 font-medium">Mile</th>
                                    <th class="py-2 font-medium">Pace</th>
                                    <th class="py-2 font-medium">Time</th>
                                    <th class="py-2 font-medium">Elevation</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($selected->splitRows() as $row)
                                    <tr class="border-t border-neutral-100">
                                        <td class="py-2">{{ $row['distance'] }}</td>
                                        <td class="py-2">
                                            <span class="mr-2">{{ $row['pace'] }}</span>
                                            <span class="inline-block h-2 rounded-full bg-[#2f6bff]" style="width: {{ $row['bar'] }}px"></span>
                                        </td>
                                        <td class="py-2 text-neutral-600">{{ $row['time'] }}</td>
                                        <td class="py-2 text-neutral-600">{{ $row['elevation'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                @elseif ($detailTab === 'elevation')
                    @if ($selected->splits->whereNotNull('elevation_feet')->isEmpty() && $selected->elevation_gain_feet === null)
                        <p class="py-6 text-sm text-neutral-500">No elevation recorded.</p>
                    @else
                        <p class="py-4 text-sm text-neutral-600">Total gain {{ $selected->elevation_gain_feet === null ? '—' : $selected->elevation_gain_feet.' ft' }}</p>
                        <div class="divide-y divide-neutral-100">
                            @foreach ($selected->splits as $split)
                                <div class="flex items-center justify-between py-2 text-sm">
                                    <span>Mile {{ $split->distanceLabel() }}</span>
                                    <span class="font-semibold">{{ $split->elevationLabel() }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    @if ($selected->average_heart_rate === null && $selected->max_heart_rate === null)
                        <p class="py-6 text-sm text-neutral-500">No heart rate recorded.</p>
                    @else
                        <div class="grid grid-cols-2 gap-3 py-4">
                            <div>
                                <p class="text-xl font-bold">{{ $selected->average_heart_rate ?? '—' }}</p>
                                <p class="text-xs text-neutral-500">Average bpm</p>
                            </div>
                            <div>
                                <p class="text-xl font-bold">{{ $selected->max_heart_rate ?? '—' }}</p>
                                <p class="text-xs text-neutral-500">Max bpm</p>
                            </div>
                        </div>
                    @endif
                @endif

                <div class="mt-4 rounded-2xl bg-neutral-50 p-3">
                    <p class="text-xs font-semibold text-neutral-500">Notes</p>
                    <p class="mt-1 text-sm">{{ $selected->notes ?: 'No notes.' }}</p>
                </div>
            </article>
        @endif
    </section>

    @if ($logging)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 px-4 py-8" wire:click.self="cancelLog" wire:keydown.escape.window="cancelLog">
            <form wire:submit="saveRun" class="w-full min-w-0 max-w-lg rounded-2xl bg-white p-5 shadow-sm" wire:click.stop>
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold">{{ $editingRun ? 'Edit Run' : 'Log Run' }}</h2>
                    <button type="button" wire:click="cancelLog" aria-label="Close" class="grid size-8 place-items-center rounded-full text-neutral-500">
                        <x-app-icon name="close" class="size-4" />
                    </button>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="block text-sm">
                        <span class="text-xs font-semibold text-neutral-500">Date</span>
                        <input type="date" wire:model.live="performedOn" class="mt-1 w-full rounded-xl border border-neutral-200 px-3 py-2">
                        @error('performedOn') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm">
                        <span class="text-xs font-semibold text-neutral-500">Start time</span>
                        <input type="time" wire:model.live="startedAt" class="mt-1 w-full rounded-xl border border-neutral-200 px-3 py-2">
                        @error('startedAt') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm sm:col-span-2">
                        <span class="text-xs font-semibold text-neutral-500">Type</span>
                        <select wire:model.live="type" class="mt-1 w-full rounded-xl border border-neutral-200 px-3 py-2">
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm">
                        <span class="text-xs font-semibold text-neutral-500">Distance (mi)</span>
                        <input wire:model.live.blur="distance" inputmode="decimal" placeholder="3.21" class="mt-1 w-full rounded-xl border border-neutral-200 px-3 py-2">
                        @error('distance') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm">
                        <span class="text-xs font-semibold text-neutral-500">Time</span>
                        <input wire:model.live.blur="duration" placeholder="32:18" class="mt-1 w-full rounded-xl border border-neutral-200 px-3 py-2">
                        @error('duration') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm">
                        <span class="text-xs font-semibold text-neutral-500">Elevation gain (ft)</span>
                        <input wire:model.live.blur="elevationGain" inputmode="numeric" placeholder="120" class="mt-1 w-full rounded-xl border border-neutral-200 px-3 py-2">
                        @error('elevationGain') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm">
                        <span class="text-xs font-semibold text-neutral-500">Calories</span>
                        <input wire:model.live.blur="calories" inputmode="numeric" placeholder="328" class="mt-1 w-full rounded-xl border border-neutral-200 px-3 py-2">
                        @error('calories') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm">
                        <span class="text-xs font-semibold text-neutral-500">Avg heart rate</span>
                        <input wire:model.live.blur="averageHeartRate" inputmode="numeric" placeholder="161" class="mt-1 w-full rounded-xl border border-neutral-200 px-3 py-2">
                        @error('averageHeartRate') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm">
                        <span class="text-xs font-semibold text-neutral-500">Max heart rate</span>
                        <input wire:model.live.blur="maxHeartRate" inputmode="numeric" placeholder="186" class="mt-1 w-full rounded-xl border border-neutral-200 px-3 py-2">
                        @error('maxHeartRate') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm sm:col-span-2">
                        <span class="text-xs font-semibold text-neutral-500">Temperature (°F)</span>
                        <input wire:model.live.blur="temperature" inputmode="numeric" placeholder="68" class="mt-1 w-full rounded-xl border border-neutral-200 px-3 py-2">
                        @error('temperature') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm sm:col-span-2">
                        <span class="text-xs font-semibold text-neutral-500">Notes</span>
                        <textarea wire:model.live.blur="notes" rows="2" placeholder="Great run. Felt strong." class="mt-1 w-full rounded-xl border border-neutral-200 px-3 py-2"></textarea>
                        @error('notes') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                </div>

                <div class="mt-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold">Splits</p>
                        <button type="button" wire:click="addSplit" class="text-sm font-semibold text-[#2f6bff]">Add split</button>
                    </div>
                    <div class="mt-2 flex flex-col gap-2">
                        @foreach ($splits as $index => $split)
                            <div wire:key="run-split-{{ $index }}" class="flex min-w-0 flex-wrap items-center gap-2">
                                <input wire:model.live.blur="splits.{{ $index }}.distance" inputmode="decimal" placeholder="Miles" aria-label="Split {{ $index + 1 }} distance" class="min-w-0 flex-1 rounded-xl border border-neutral-200 px-2 py-2 text-sm">
                                <input wire:model.live.blur="splits.{{ $index }}.duration" placeholder="10:16" aria-label="Split {{ $index + 1 }} time" class="min-w-0 flex-1 rounded-xl border border-neutral-200 px-2 py-2 text-sm">
                                <input wire:model.live.blur="splits.{{ $index }}.elevation" inputmode="numeric" placeholder="ft" aria-label="Split {{ $index + 1 }} elevation" class="min-w-0 flex-1 rounded-xl border border-neutral-200 px-2 py-2 text-sm">
                                <button type="button" wire:click="removeSplit({{ $index }})" aria-label="Remove split {{ $index + 1 }}" class="grid size-9 shrink-0 place-items-center text-neutral-400">
                                    <x-app-icon name="trash" class="size-4" />
                                </button>
                            </div>
                            @error('splits.'.$index) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-between gap-3">
                    @if ($editingRun)
                        <button type="button" wire:click="deleteRun" wire:confirm="Delete this run?" class="text-sm font-semibold text-red-600">Delete</button>
                    @else
                        <span></span>
                    @endif
                    <div class="flex gap-2">
                        <button type="button" wire:click="cancelLog" class="rounded-xl px-4 py-2 text-sm font-semibold text-neutral-600">Cancel</button>
                        <button type="submit" class="rounded-xl bg-[#2f6bff] px-4 py-2 text-sm font-semibold text-white">Save Run</button>
                    </div>
                </div>
            </form>
        </div>
    @endif
</div>
