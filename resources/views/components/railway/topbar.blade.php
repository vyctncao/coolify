@props([
    'project',
    'environment',
    'projects' => null,
    'environments' => null,
    'active' => 'architecture',
])

@php
    $projects = $projects ?? collect();
    $environments = $environments ?? collect();
    $team = currentTeam();
    $user = auth()->user();
    $defaultEnvUuid = function ($p) {
        $envs = $p->environments;
        return optional($envs->firstWhere('name', 'production') ?? $envs->first())->uuid;
    };
    $routeArgs = ['project_uuid' => $project->uuid, 'environment_uuid' => $environment->uuid];

    // Railway puts these four in the header, right-aligned, not in a side
    // rail. Order is fixed and matches railway.com exactly.
    $tabs = [
        'architecture' => ['label' => 'Architecture', 'route' => route('railway.canvas', $routeArgs)],
        'observability' => ['label' => 'Observability', 'route' => route('railway.observability', $routeArgs)],
        'logs' => ['label' => 'Logs', 'route' => route('railway.logs', $routeArgs)],
        'settings' => ['label' => 'Settings', 'route' => route('railway.settings', $routeArgs)],
    ];

    $initials = collect(explode(' ', trim((string) ($user->name ?? 'U'))))
        ->filter()
        ->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('');
@endphp

<header class="relative z-40 flex items-center gap-1 h-[72px] px-4 border-b shrink-0"
    style="border-color: var(--color-rw-border); background: var(--color-rw-surface);">
    {{-- Brand --}}
    <a href="{{ route('railway.projects') }}" wire:navigate class="rw-icon-btn hover:rw-icon-btn-hover">
        <x-railway.logo size="w-5 h-5" />
    </a>

    <span class="text-rw-faint select-none">/</span>

    {{-- Project switcher --}}
    <div class="relative" x-data="{ open: false }">
        <button type="button" @click="open = !open" @click.outside="open = false"
            class="flex items-center gap-2 rounded-md px-2 h-8 text-[14px] font-medium text-rw-text hover:bg-rw-hover">
            <span class="max-w-[180px] truncate">{{ $project->name }}</span>
            <svg class="w-3.5 h-3.5 text-rw-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <div x-show="open" x-cloak x-transition.origin.top.left class="absolute left-0 top-14 w-64 rw-menu">
            <div class="px-2.5 py-1.5 rw-section-label flex items-center gap-2">
                <span class="inline-block w-4 h-4 rounded-full" style="background: linear-gradient(135deg, var(--color-rw-accent), var(--color-rw-danger-bright));"></span>
                {{ $team->name }}
            </div>
            <div class="max-h-72 overflow-y-auto rw-scroll">
                @foreach ($projects as $p)
                    @php $envUuid = $defaultEnvUuid($p); @endphp
                    @if ($envUuid)
                        <a href="{{ route('railway.canvas', ['project_uuid' => $p->uuid, 'environment_uuid' => $envUuid]) }}" wire:navigate
                            class="rw-menu-item hover:rw-menu-item-hover">
                            <svg class="w-3.5 h-3.5 {{ $p->uuid === $project->uuid ? 'text-rw-accent-hover' : 'text-transparent' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 5 5L20 7"/></svg>
                            <span class="truncate">{{ $p->name }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
            <div class="mt-1 pt-1 border-t" style="border-color: var(--color-rw-border);">
                <a href="{{ route('project.index') }}" wire:navigate class="rw-menu-item hover:rw-menu-item-hover" style="color: var(--color-rw-accent-hover);">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    New Project
                </a>
            </div>
        </div>
    </div>

    <span class="text-rw-faint select-none">/</span>

    {{-- Environment switcher --}}
    <div class="relative" x-data="{ open: false }">
        <button type="button" @click="open = !open" @click.outside="open = false"
            class="flex items-center gap-2 rounded-md px-2 h-8 text-[14px] font-medium text-rw-text hover:bg-rw-hover">
            <span class="max-w-[180px] truncate">{{ $environment->name }}</span>
            <svg class="w-3.5 h-3.5 text-rw-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <div x-show="open" x-cloak x-transition.origin.top.left class="absolute left-0 top-14 w-60 rw-menu">
            <div class="px-2.5 py-1.5 rw-section-label">Environments</div>
            <div class="max-h-72 overflow-y-auto rw-scroll">
                @foreach ($environments as $env)
                    <a href="{{ route('railway.canvas', ['project_uuid' => $project->uuid, 'environment_uuid' => $env->uuid]) }}" wire:navigate
                        class="rw-menu-item hover:rw-menu-item-hover">
                        <svg class="w-3.5 h-3.5 {{ $env->uuid === $environment->uuid ? 'text-rw-accent-hover' : 'text-transparent' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 5 5L20 7"/></svg>
                        <span class="truncate">{{ $env->name }}</span>
                    </a>
                @endforeach
            </div>
            <div class="mt-1 pt-1 border-t" style="border-color: var(--color-rw-border);">
                <livewire:railway.new-environment :project="$project" :key="'rw-newenv-'.$project->uuid" />
            </div>
        </div>
    </div>

    <div class="flex-1"></div>

    {{-- Section tabs. Railway right-aligns these in the header and marks the
         active one with a violet rule sitting on the header's bottom border. --}}
    <nav class="flex items-stretch gap-6 h-full mr-4">
        @foreach ($tabs as $key => $tab)
            <a href="{{ $tab['route'] }}" wire:navigate
                class="rw-tab hover:rw-tab-hover {{ $active === $key ? 'rw-tab-active' : '' }}">
                {{ $tab['label'] }}
                @if ($active === $key)
                    <span class="rw-tab-active-marker"></span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="mx-1 w-px h-5" style="background: var(--color-rw-border);"></div>

    {{-- Agent is this fork's addition; kept as a quiet icon button so it
         doesn't disturb Railway's header silhouette. --}}
    <button type="button" @click="$dispatch('openRailwayAgent')" class="rw-icon-btn hover:rw-icon-btn-hover" title="Agent">
        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
    </button>

    <a href="{{ route('railway.settings', $routeArgs) }}" wire:navigate
        class="rw-tab hover:rw-tab-hover px-2">Share</a>

    <div class="relative ml-1" x-data="{ open: false }">
        <button type="button" @click="open = !open" @click.outside="open = false"
            class="flex items-center justify-center w-8 h-8 rounded-full text-[11px] font-semibold text-white"
            style="background: linear-gradient(135deg, var(--color-rw-blue-500), var(--color-rw-accent));"
            title="{{ $user->name ?? '' }}">
            {{ $initials }}
        </button>
        <div x-show="open" x-cloak x-transition.origin.top.right class="absolute right-0 top-14 w-56 rw-menu">
            <div class="px-2.5 py-1.5">
                <div class="text-[13px] font-medium text-rw-text truncate">{{ $user->name }}</div>
                <div class="text-[12px] text-rw-subtle truncate">{{ $user->email }}</div>
            </div>
            <div class="my-1 border-t" style="border-color: var(--color-rw-border);"></div>
            <a href="{{ route('profile') }}" wire:navigate class="rw-menu-item hover:rw-menu-item-hover">Profile</a>
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
