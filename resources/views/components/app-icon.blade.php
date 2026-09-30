@props(['name'])

@php
    $icons = [
        'lock' => 'fa-lock',
        'home' => 'fa-house',
        'log' => 'fa-plus',
        'strength' => 'fa-weight-hanging',
        'dumbbell' => 'fa-dumbbell',
        'progress' => 'fa-chart-line',
        'check' => 'fa-check',
        'chevron-left' => 'fa-chevron-left',
        'chevron-right' => 'fa-chevron-right',
        'chevron-down' => 'fa-chevron-down',
        'chevron-up' => 'fa-chevron-up',
        'run' => 'fa-person-running',
        'user' => 'fa-user',
        'timer' => 'fa-stopwatch',
        'clock' => 'fa-clock',
        'fire' => 'fa-fire',
        'calendar' => 'fa-calendar',
        'star' => 'fa-star',
        'medal' => 'fa-medal',
        'volume' => 'fa-chart-column',
        'target' => 'fa-bullseye',
        'mobility' => 'fa-person-walking',
        'search' => 'fa-magnifying-glass',
        'trash' => 'fa-trash',
        'close' => 'fa-xmark',
        'info' => 'fa-circle-info',
        'bookmark' => 'fa-bookmark',
        'minus' => 'fa-minus',
    ];
@endphp

<i {{ $attributes->class(['app-icon', 'fa-solid', $icons[$name]]) }} aria-hidden="true"></i>
