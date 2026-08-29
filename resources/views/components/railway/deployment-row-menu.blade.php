@props([
    'deployment',
    'isApplication' => false,
])

@php
    // Railway gates its row menu on the deployment's own canRedeploy /
    // canRollback flags. Coolify has no equivalent field, so the same
    // condition is derived: you need an application and a commit to re-run.
    $commit = $deployment['fullCommit'] ?? null;
    $canDeployCommit = $isApplication && filled($commit);
@endphp

<div class="relative shrink-0" x-data="{ open: false }">
    <button type="button" @click.stop="open = !open" @click.outside="open = false"
        class="rw-icon-btn hover:rw-icon-btn-hover rw-press-sm rw-focus !w-7 !h-7" title="Deployment actions">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
    </button>

    <div x-show="open" x-cloak x-transition.origin.top.right class="absolute right-0 top-8 z-50 w-44 rw-menu">
        <button type="button" wire:click="openDeploymentLogs({{ $deployment['id'] }})" @click="open = false"
            class="rw-menu-item hover:rw-menu-item-hover w-full">
            <svg class="w-3.5 h-3.5 text-rw-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3h11l5 5v13H4z"/><path d="M14 3v5h5M8 13h8M8 17h5"/></svg>
            View logs
        </button>

        @if ($canDeployCommit)
            <button type="button" wire:click="redeployCommit('{{ $commit }}')" @click="open = false"
                class="rw-menu-item hover:rw-menu-item-hover w-full">
                <svg class="w-3.5 h-3.5 text-rw-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 0 1 15-6.7L21 8M21 3v5h-5"/></svg>
                Redeploy
            </button>
            <button type="button" wire:click="rollbackTo('{{ $commit }}')" @click="open = false"
                wire:confirm="Roll back to {{ $deployment['commit'] }}?"
                class="rw-menu-item hover:rw-menu-item-hover w-full">
                <svg class="w-3.5 h-3.5 text-rw-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-15-6.7L3 13"/></svg>
                Rollback
            </button>
        @endif
    </div>
</div>
