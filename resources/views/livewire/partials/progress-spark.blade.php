@if ($bars !== [])
    <svg viewBox="0 0 {{ max(count($bars), 1) * 8 }} 36" class="h-10 w-16 shrink-0" aria-hidden="true">
        @foreach ($bars as $index => $height)
            <rect
                x="{{ $index * 8 + 1 }}"
                y="{{ 34 - ($height / 100 * 30) }}"
                width="5"
                height="{{ max(2, $height / 100 * 30) }}"
                rx="1.5"
                fill="{{ $color }}"
            />
        @endforeach
    </svg>
@endif
