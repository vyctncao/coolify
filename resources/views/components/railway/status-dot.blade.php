@props([
    'status' => '',
    'showLabel' => true,
])

{{--
    Renders the Railway-style status dot + label from a Coolify status string.
    Coolify status format is "{state}:{health}" (e.g. running:healthy). The green
    "Online" state is any status whose state prefix starts with `running`.
--}}
@php
    $raw = strtolower(trim((string) $status));
    $state = str_contains($raw, ':') ? explode(':', $raw)[0] : $raw;

    if (str_starts_with($state, 'running')) {
        $variant = 'online';
        $label = 'Online';
    } elseif (in_array($state, ['degraded', 'unhealthy'], true)) {
        $variant = 'degraded';
        $label = 'Degraded';
    } elseif (in_array($state, ['starting', 'restarting'], true)) {
        $variant = 'degraded';
        $label = ucfirst($state);
    } elseif ($state === '' || $state === 'unknown') {
        $variant = 'offline';
        $label = 'Unknown';
    } else {
        $variant = 'offline';
        $label = 'Offline';
    }

    $dotClass = match ($variant) {
        'online' => 'rw-dot rw-dot-online',
        'degraded' => 'rw-dot rw-dot-degraded',
        default => 'rw-dot rw-dot-offline',
    };
    // Railway renders a node's status line in gray-600, the same weight as its
    // "Just deployed" meta, and reserves colour for online / attention states.
    $labelClass = match ($variant) {
        'online' => 'text-rw-online-bright',
        'degraded' => 'text-rw-warn',
        default => 'text-rw-muted',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <span class="{{ $dotClass }}"></span>
    @if ($showLabel)
        <span class="text-sm {{ $labelClass }}">{{ $label }}</span>
    @endif
</span>
