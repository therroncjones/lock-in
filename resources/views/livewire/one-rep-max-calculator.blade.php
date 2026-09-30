<div class="mx-auto flex w-full max-w-5xl flex-col gap-4 px-4 py-5 md:px-8 md:py-8">
    <header>
        <h1 class="text-2xl font-bold tracking-tight">One Rep Max</h1>
        <p class="mt-1 max-w-xl text-sm text-neutral-500">Enter a one rep max. Percentages and plates are calculated from that weight.</p>
    </header>

    <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <label class="flex w-full flex-col gap-1 lg:max-w-xs">
                <span class="text-[11px] font-semibold tracking-wide text-neutral-400">ONE REP MAX (LBS)</span>
                <input
                    type="text"
                    inputmode="decimal"
                    wire:model.live="max"
                    placeholder="Weight"
                    aria-label="One rep max weight"
                    class="rounded-xl border border-neutral-200 bg-white px-3 py-2 text-base outline-none focus:border-[#2f6bff]"
                >
            </label>
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
        @error('max')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </section>

    @if ($rows === [])
        <p class="rounded-3xl border border-dashed border-neutral-300 px-4 py-6 text-center text-sm text-neutral-500">Enter a one rep max to see the loads.</p>
    @else
        <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
            <h2 class="text-sm font-semibold">Percentage Based Weights</h2>
            <ul class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-9">
                @foreach ($rows as $row)
                    @if ($row['key'] !== 'custom')
                        <li class="flex">
                            <button
                                type="button"
                                wire:click="selectPercent('{{ $row['key'] }}')"
                                class="flex h-16 w-full flex-col items-center justify-center rounded-xl border px-1 text-center whitespace-nowrap {{ ($selected['key'] ?? '') === $row['key'] ? 'border-[#2f6bff] bg-[#e8f1ff]' : 'border-neutral-200 bg-white' }}"
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
                            wire:model.live.blur="customPercent"
                            placeholder="65"
                            aria-label="Custom percent"
                            class="w-full text-base outline-none"
                        >
                        <span class="text-sm text-neutral-400">%</span>
                    </span>
                </label>
                <p class="flex h-16 w-full min-w-0 items-center rounded-xl bg-[#e8f1ff] px-4 text-2xl font-bold text-[#2f6bff]">
                    <span class="mr-2 text-sm font-semibold text-neutral-500">{{ $selected['label'] ?? '' }}</span>
                    {{ $selected['target'] ?? '—' }}
                    <span class="ml-1 text-sm font-semibold">lbs</span>
                </p>
            </div>
            @if ((int) $barWeight > 0 && $selected)
                <div class="mt-4">
                    <h3 class="text-sm font-semibold">Plate Calculator <span class="font-normal text-neutral-500">(per side)</span></h3>
                    <div class="mt-3 flex flex-wrap items-center gap-4">
                        @if ($selected['plateCounts'] === [])
                            <span class="text-sm text-neutral-500">Bar only</span>
                        @else
                            @foreach ($selected['plateCounts'] as $plate)
                                <x-weight-plate :size="$plate['size']" :count="$plate['count']" />
                            @endforeach
                        @endif
                        <div class="ml-auto border-l border-neutral-200 pl-4">
                            <p class="text-xs text-neutral-500">Total:</p>
                            <p class="text-xl font-bold tracking-tight">{{ $selected['loaded'] }} lbs</p>
                        </div>
                    </div>
                </div>
            @endif
            @error('customPercent')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </section>
    @endif
</div>
