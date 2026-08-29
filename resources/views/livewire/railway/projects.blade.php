<div>
    <x-slot:title>Projects | Coolify</x-slot>

    {{-- Railway's dashboard is a single centred column under a bare header —
         no sidebar. Cards are whole projects; environments are chosen from the
         switcher once you're inside one. --}}
    <div class="flex flex-col h-screen w-full overflow-hidden">
        <x-railway.dashboard-topbar />

        <main class="flex-1 min-h-0 overflow-y-auto rw-scroll">
            {{-- Railway's 1160px container is the *content* width: its grid measures
                 38px in from each side of the frame and spans a full 1160. So the
                 gutter sits outside the token, not inside it. --}}
            <div class="mx-auto px-6 pt-14 pb-16" style="max-width: calc(var(--container-rw) + 3rem);">
                {{-- Workspace header --}}
                <div class="flex items-center justify-between gap-4 mb-8">
                    <livewire:railway.team-switcher />

                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('team.index') }}" wire:navigate
                            class="rw-btn hover:rw-btn-hover rw-press rw-focus !px-2.5" title="Workspace settings">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        </a>

                        {{-- Railway's primary dashboard action is outlined in
                             the accent, not filled. --}}
                        <a href="{{ route('project.index') }}" wire:navigate
                            class="inline-flex items-center gap-1.5 rounded-md border px-3 h-[34px] text-sm font-medium rw-press rw-focus whitespace-nowrap"
                            style="border-color: var(--color-rw-accent); color: var(--color-rw-accent-hover);">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                            New Project
                        </a>
                    </div>
                </div>

                {{-- Project grid --}}
                @if ($groups->isNotEmpty())
                    {{-- 3 columns of 376px on a 16px gap sums to exactly 1160px, which is
                         Railway's container width — so the gap is 16, not 20. --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach ($groups as $project)
                            <a href="{{ $project['url'] ?? route('project.edit', ['project_uuid' => $project['uuid']]) }}" wire:navigate
                                class="rw-card hover:rw-card-hover group/card flex flex-col p-6 min-h-[185px]">
                                <h2 class="text-[17px] font-semibold text-rw-text leading-snug">{{ $project['name'] }}</h2>

                                @if ($project['description'])
                                    <p class="mt-2 text-sm text-rw-subtle line-clamp-2">{{ $project['description'] }}</p>
                                @endif

                                <div class="flex-1"></div>

                                <div class="flex items-center gap-2 text-sm text-rw-subtle">
                                    <span>{{ $project['service_count'] }} {{ $project['service_count'] === 1 ? 'service' : 'services' }}</span>
                                    @if ($project['environment_count'] > 1)
                                        <span class="text-rw-faint">·</span>
                                        <span>{{ $project['environment_count'] }} environments</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="rw-card flex flex-col items-center justify-center py-20 text-center">
                        <div class="text-sm text-rw-muted">No projects yet.</div>
                        <a href="{{ route('project.index') }}" wire:navigate
                            class="mt-4 rw-btn-primary hover:rw-btn-primary-hover rw-press rw-focus">Create your first project</a>
                    </div>
                @endif
            </div>
        </main>

        <x-railway.command-palette :projects="$allProjects" />
    </div>
</div>
