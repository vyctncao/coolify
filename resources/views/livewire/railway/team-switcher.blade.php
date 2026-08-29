{{-- Railway's workspace header: avatar, workspace name, plan line, chevron. --}}
<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button type="button" @click="open = !open"
        class="flex items-center gap-3 rounded-lg px-2 py-1.5 -ml-2 hover:bg-rw-hover">
        <span class="inline-block w-10 h-10 rounded-full shrink-0"
            style="background: linear-gradient(135deg, var(--color-rw-accent), var(--color-rw-danger-bright));"></span>
        <div class="min-w-0 text-left">
            <div class="text-[17px] font-semibold text-rw-text truncate leading-tight">{{ $current->name }}</div>
            <div class="text-[13px] text-rw-subtle truncate leading-tight mt-0.5">{{ $planLabel }}</div>
        </div>
        <svg class="w-4 h-4 text-rw-faint shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
    </button>

    <div x-show="open" x-cloak x-transition.origin.top.left class="absolute left-0 top-16 z-50 w-64 rw-menu">
        <div class="px-2.5 py-1.5 rw-section-label">Workspaces</div>
        <div class="max-h-72 overflow-y-auto rw-scroll">
            @foreach ($teams as $t)
                <button type="button" wire:click="switchTo({{ $t->id }})" @click="open = false"
                    class="rw-menu-item hover:rw-menu-item-hover w-full">
                    <span class="inline-block w-4 h-4 rounded-full shrink-0"
                        style="background: linear-gradient(135deg, var(--color-rw-blue-500), var(--color-rw-accent));"></span>
                    <span class="truncate flex-1 text-left">{{ $t->name }}</span>
                    @if ($t->id === $current->id)
                        <svg class="w-3.5 h-3.5 shrink-0" style="color: var(--color-rw-accent-hover);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 5 5L20 7"/></svg>
                    @endif
                </button>
            @endforeach
        </div>
        <div class="mt-1 pt-1 border-t" style="border-color: var(--color-rw-border);">
            <a href="{{ route('team.index') }}" wire:navigate @click="open = false" class="rw-menu-item hover:rw-menu-item-hover">
                <svg class="w-3.5 h-3.5 text-rw-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0M16 3.1a3 3 0 0 1 0 5.8M21 20a6 6 0 0 0-4-5.6"/></svg>
                Manage &amp; create teams
            </a>
        </div>
    </div>
</div>
