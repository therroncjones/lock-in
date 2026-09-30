<svg viewBox="0 0 {{ $chart['width'] }} {{ $chart['height'] }}" class="mt-2 w-full" role="img" aria-label="{{ $label }}">
    @foreach ($chart['ticks'] as $tick)
        <line x1="{{ $chart['left'] }}" x2="{{ $chart['width'] - $chart['right'] }}" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}" stroke="#e5e7eb" />
        <text x="0" y="{{ $tick['y'] + 4 }}" fill="#a3a3a3" font-size="8">{{ $tick['label'] }}</text>
    @endforeach
    @if ($chart['area'] !== '')
        <polygon points="{{ $chart['area'] }}" fill="#2f6bff" fill-opacity="0.12" />
        <polyline points="{{ $chart['line'] }}" fill="none" stroke="#2f6bff" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
    @endif
    @foreach ($chart['dots'] as $dot)
        <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="3.5" fill="#2f6bff" />
    @endforeach
    @foreach ($chart['labels'] as $month)
        <text x="{{ $month['x'] }}" y="{{ $chart['height'] - 6 }}" fill="#a3a3a3" font-size="8" text-anchor="middle">{{ $month['text'] }}</text>
    @endforeach
    <rect x="{{ $chart['end']['x'] }}" y="{{ $chart['end']['y'] }}" width="{{ $chart['end']['width'] }}" height="16" rx="8" fill="#1e293b" />
    <text x="{{ $chart['end']['textX'] }}" y="{{ $chart['end']['y'] + 11.5 }}" fill="#ffffff" font-size="8" text-anchor="middle">{{ $chart['end']['label'] }}</text>
</svg>
