@php
    $links = [
        ['route' => 'home', 'label' => 'Home', 'icon' => 'home'],
        ['route' => 'one-rep-max', 'label' => 'One Rep Max', 'icon' => 'dumbbell'],
        ['route' => 'log', 'label' => 'Strength', 'icon' => 'strength'],
        ['route' => 'runs', 'label' => 'Runs', 'icon' => 'run'],
        ['route' => 'progress', 'label' => 'Progress', 'icon' => 'progress'],
    ];
@endphp

@if ($variant === 'sidebar')
    <nav class="flex min-h-0 flex-1 flex-col gap-1 overflow-y-auto px-3" aria-label="Primary">
        @foreach ($links as $link)
            @php $active = request()->routeIs($link['route']); @endphp
            <a
                href="{{ route($link['route']) }}"
                @class([
                    'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                    'bg-black text-white' => $active,
                    'text-neutral-600 hover:bg-neutral-100 hover:text-black' => ! $active,
                ])
                @if ($active) aria-current="page" @endif
            >
                <x-app-icon :name="$link['icon']" class="size-5 shrink-0" />
                <span class="min-w-0 flex-1">{{ $link['label'] }}</span>
                @if ($missingOneRepMaxCount > 0 && $link['route'] === 'one-rep-max')
                    <span class="ml-auto grid size-5 shrink-0 place-items-center rounded-full text-[11px] font-bold text-white" style="background-color: #e23b3b; line-height: 1" data-one-rep-max-alert="{{ $missingOneRepMaxCount }}" aria-label="{{ $missingOneRepMaxCount }} {{ $missingOneRepMaxCount === 1 ? 'one rep max' : 'one rep maxes' }} to add">{{ $missingOneRepMaxCount }}</span>
                @endif
            </a>
        @endforeach
    </nav>
@else
    <nav class="flex" aria-label="Primary">
        @foreach ($links as $link)
            @php $active = request()->routeIs($link['route']); @endphp
            <a
                href="{{ route($link['route']) }}"
                @class([
                    'flex min-w-0 flex-1 flex-col items-center gap-1 px-1 py-2 text-center text-[11px] font-medium',
                    'text-black' => $active,
                    'text-neutral-400' => ! $active,
                ])
                @if ($active) aria-current="page" @endif
            >
                <span class="relative inline-flex">
                    <x-app-icon :name="$link['icon']" class="size-6" />
                    @if ($missingOneRepMaxCount > 0 && $link['route'] === 'one-rep-max')
                        <span class="absolute grid size-4 place-items-center rounded-full font-bold text-white" style="background-color: #e23b3b; top: -6px; right: -8px; font-size: 10px; line-height: 1" data-one-rep-max-alert="{{ $missingOneRepMaxCount }}" aria-label="{{ $missingOneRepMaxCount }} {{ $missingOneRepMaxCount === 1 ? 'one rep max' : 'one rep maxes' }} to add">{{ $missingOneRepMaxCount }}</span>
                    @endif
                </span>
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
@endif
