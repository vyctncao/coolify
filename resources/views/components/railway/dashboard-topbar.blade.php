@php
    $user = auth()->user();
    $initials = collect(explode(' ', trim((string) ($user->name ?? 'U'))))
        ->filter()
        ->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('');
@endphp

{{-- Railway's dashboard header: just the mark on the left and Help + the
     account menu on the right. Everything else lives in the page body. --}}
<header class="flex items-center h-[72px] px-6 shrink-0">
    <a href="{{ route('railway.projects') }}" wire:navigate class="rw-icon-btn hover:rw-icon-btn-hover rw-press-sm">
        <x-railway.logo size="w-6 h-6" />
    </a>

    <div class="flex-1"></div>

    <a href="https://coolify.io/docs" target="_blank" rel="noopener"
        class="text-sm font-medium text-rw-subtle hover:text-rw-text mr-4 rw-focus">Help</a>

    {{-- Account menu. Coolify's instance-level nav (servers, sources, keys,
         ...) has no Railway equivalent on this screen, so it lives here —
         the same place Railway keeps account and workspace settings. --}}
    <div class="relative" x-data="{ open: false }">
        <button type="button" @click="open = !open" @click.outside="open = false"
            class="flex items-center gap-1.5 rounded-full rw-press-sm rw-focus" title="{{ $user->name ?? '' }}">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-[11px] font-semibold text-white"
                style="background: linear-gradient(135deg, var(--color-rw-blue-500), var(--color-rw-accent));">{{ $initials }}</span>
            <svg class="w-4 h-4 text-rw-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
        </button>

        <div x-show="open" x-cloak x-transition.origin.top.right class="absolute right-0 top-11 z-50 w-64 rw-menu">
            <div class="px-2.5 py-1.5">
                <div class="text-sm font-medium text-rw-text truncate">{{ $user->name }}</div>
                <div class="text-xs text-rw-subtle truncate">{{ $user->email }}</div>
            </div>

            <div class="my-1 border-t" style="border-color: var(--color-rw-border);"></div>
            <div class="px-2.5 py-1 rw-section-label">Infrastructure</div>
            <a href="{{ route('server.index') }}" wire:navigate class="rw-menu-item hover:rw-menu-item-hover">Servers</a>
            <a href="{{ route('source.all') }}" wire:navigate class="rw-menu-item hover:rw-menu-item-hover">Sources</a>
            <a href="{{ route('security.private-key.index') }}" wire:navigate class="rw-menu-item hover:rw-menu-item-hover">Private Keys</a>
            <a href="{{ route('destination.index') }}" wire:navigate class="rw-menu-item hover:rw-menu-item-hover">Destinations</a>
            <a href="{{ route('shared-variables.index') }}" wire:navigate class="rw-menu-item hover:rw-menu-item-hover">Shared Variables</a>

            <div class="my-1 border-t" style="border-color: var(--color-rw-border);"></div>
            <div class="px-2.5 py-1 rw-section-label">Account</div>
            <a href="{{ route('profile') }}" wire:navigate class="rw-menu-item hover:rw-menu-item-hover">Profile</a>
            <a href="{{ route('team.index') }}" wire:navigate class="rw-menu-item hover:rw-menu-item-hover">Teams</a>
            <a href="{{ route('notifications.email') }}" wire:navigate class="rw-menu-item hover:rw-menu-item-hover">Notifications</a>
            <a href="{{ route('settings.index') }}" wire:navigate class="rw-menu-item hover:rw-menu-item-hover">Settings</a>
            <a href="{{ route('dashboard.classic') }}" wire:navigate class="rw-menu-item hover:rw-menu-item-hover">Classic UI</a>

            <div class="my-1 border-t" style="border-color: var(--color-rw-border);"></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rw-menu-item hover:rw-menu-item-hover w-full text-left">Log out</button>
            </form>
        </div>
    </div>
</header>
