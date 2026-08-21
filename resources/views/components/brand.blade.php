@props(['variant' => 'wordmark'])

@php($brand = config('branding.name'))

<a href="/" {{ wireNavigate() }} title="{{ $brand }}"
    {{ $attributes->class([
        'hover:opacity-80 transition-opacity',
        'font-bold tracking-tight dark:text-white' => $variant === 'wordmark',
    ]) }}>
    @if ($variant === 'mark')
        <img src="{{ asset(config('branding.logo.default')) }}" alt="{{ $brand }}" class="w-6 h-6" />
    @else
        {{ $brand }}
    @endif
</a>
