@props(['size', 'count'])

@php
    $styles = [
        '45' => ['color' => '#e23b3b', 'diameter' => 56],
        '35' => ['color' => '#f0b429', 'diameter' => 50],
        '25' => ['color' => '#2f6bff', 'diameter' => 44],
        '15' => ['color' => '#2f9e62', 'diameter' => 38],
        '10' => ['color' => '#f4f5f7', 'diameter' => 34, 'edge' => '#b8bcc4'],
        '5' => ['color' => '#d5d8df', 'diameter' => 28, 'edge' => '#8e939c'],
        '2.5' => ['color' => '#c5c9d1', 'diameter' => 22, 'edge' => '#8e939c'],
    ];
    $style = $styles[(string) $size] ?? ['color' => '#d5d8df', 'diameter' => 28, 'edge' => '#8e939c'];
    $diameter = $style['diameter'];
    $hole = max(8, (int) round($diameter * 0.28));
    $edge = $style['edge'] ?? 'rgba(0, 0, 0, 0.16)';
@endphp

<span class="inline-flex items-center gap-2">
    <span
        class="grid shrink-0 place-items-center rounded-full"
        style="width: {{ $diameter }}px; height: {{ $diameter }}px; background: {{ $style['color'] }}; box-shadow: inset 0 0 0 2px {{ $edge }};"
        aria-hidden="true"
    >
        <span class="rounded-full bg-white" style="width: {{ $hole }}px; height: {{ $hole }}px; box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.08);"></span>
    </span>
    <span class="leading-tight">
        <span class="block text-sm font-semibold">{{ $size }} lb</span>
        <span class="block text-xs text-neutral-500">× {{ $count }}</span>
    </span>
</span>
