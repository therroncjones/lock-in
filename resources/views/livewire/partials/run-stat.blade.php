<article class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-neutral-200/70">
    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
            <span @class(['grid size-8 place-items-center rounded-full', $iconClass])>
                <x-app-icon :name="$icon" class="size-4" />
            </span>
            <p class="text-xs text-neutral-500">{{ $label }}</p>
        </div>
        <p class="mt-2 text-2xl font-bold">{{ $value }}</p>
        @if ($change !== null)
            <p @class([
                'mt-1 text-xs font-semibold',
                'text-emerald-500' => $lowerIsBetter ? $change <= 0 : $change >= 0,
                'text-red-600' => $lowerIsBetter ? $change > 0 : $change < 0,
            ])>{{ $change >= 0 ? '↑ +'.$change.'%' : '↓ '.$change.'%' }} {{ $changeLabel }}</p>
        @endif
    </div>
    @include('livewire.partials.progress-spark', ['bars' => $bars, 'color' => $color])
</article>
