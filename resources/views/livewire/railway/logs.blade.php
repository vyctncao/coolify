<div>
    <x-slot:title>{{ $project->name }} · Logs | Coolify</x-slot>

    <x-railway.project-chrome :project="$project" :environment="$environment"
        :projects="$allProjects" :environments="$allEnvironments" active="logs">

        <div class="flex flex-col h-full" x-data="{ columns: { timestamp: true, service: true, replica: true } }">
            {{-- Toolbar. Railway's order is: time range, then the query input,
                 then the filter / search / column controls on the right. --}}
            <div class="flex items-center gap-2 px-4 py-3 border-b shrink-0" style="border-color: var(--color-rw-border);">
                <div class="relative shrink-0" x-data="{ open: false }">
                    <button type="button" @click="open = !open" @click.outside="open = false"
                        class="rw-btn hover:rw-btn-hover rw-press rw-focus">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        {{ $ranges[$range] ?? 'All Time' }}
                        <svg class="w-3.5 h-3.5 text-rw-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top.left class="absolute left-0 top-10 z-40 w-44 rw-menu">
                        @foreach ($ranges as $key => $label)
                            <button type="button" wire:click="setRange('{{ $key }}')" @click="open = false"
                                class="rw-menu-item hover:rw-menu-item-hover w-full">
                                <svg class="w-3.5 h-3.5 {{ $range === $key ? '' : 'text-transparent' }}" style="{{ $range === $key ? 'color: var(--color-rw-accent-hover)' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 5 5L20 7"/></svg>
                                <span class="text-left flex-1">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-1 rounded-md border px-3 h-[34px]"
                    style="border-color: var(--color-rw-border); background: var(--color-rw-bg);">
                    <input type="text" wire:model.live.debounce.400ms="filter"
                        placeholder='Filter logs using "", (), AND, OR, -'
                        class="flex-1 bg-transparent text-sm text-rw-text placeholder:text-rw-faint focus:outline-none border-0 p-0" />
                    @if ($filter !== '')
                        <button type="button" wire:click="$set('filter', '')" class="text-rw-faint hover:text-rw-text rw-focus" title="Clear filter">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                    @endif
                </div>

                <button type="button" wire:click="refreshLogs" wire:loading.attr="disabled" wire:target="refreshLogs"
                    class="rw-icon-btn hover:rw-icon-btn-hover rw-press-sm rw-focus border" style="border-color: var(--color-rw-border);" title="Refresh">
                    <svg class="w-4 h-4" wire:loading.remove wire:target="refreshLogs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 12a9 9 0 0 1 15-6.7L21 8M21 3v5h-5M21 12a9 9 0 0 1-15 6.7L3 16M3 21v-5h5"/></svg>
                    <svg class="w-4 h-4 animate-spin" wire:loading wire:target="refreshLogs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.2-8.6"/></svg>
                </button>

                {{-- Column toggles, matching Railway's gear popover. --}}
                <div class="relative shrink-0" x-data="{ open: false }">
                    <button type="button" @click="open = !open" @click.outside="open = false"
                        class="rw-icon-btn hover:rw-icon-btn-hover rw-press-sm rw-focus border" style="border-color: var(--color-rw-border);" title="Columns">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top.right class="absolute right-0 top-10 z-40 w-44 rw-menu">
                        <template x-for="col in ['timestamp','service','replica']" :key="col">
                            <button type="button" @click="columns[col] = !columns[col]" class="rw-menu-item hover:rw-menu-item-hover w-full">
                                <span class="inline-flex items-center justify-center w-4 h-4 rounded-sm border shrink-0"
                                    :style="columns[col]
                                        ? 'background: var(--color-rw-accent); border-color: var(--color-rw-accent)'
                                        : 'border-color: var(--color-rw-border-strong)'">
                                    <svg x-show="columns[col]" class="w-3 h-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 5 5L20 7"/></svg>
                                </span>
                                <span class="capitalize" x-text="col"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            @if (! empty($visibleRows))
                {{-- Column header --}}
                <div class="flex items-center gap-4 px-4 py-2 border-b text-xs font-medium text-rw-subtle shrink-0"
                    style="border-color: var(--color-rw-border);">
                    <span class="w-[132px] shrink-0" x-show="columns.timestamp">Timestamp</span>
                    <span class="w-[150px] shrink-0 truncate" x-show="columns.service">Service</span>
                    <span class="w-[96px] shrink-0" x-show="columns.replica">Replica</span>
                    <span class="flex-1">Message</span>
                </div>

                <div class="flex-1 min-h-0 overflow-auto rw-scroll font-mono text-[12px] leading-[1.55]">
                    @foreach ($visibleRows as $row)
                        <div class="group/logline flex items-start gap-4 px-4 py-[3px] hover:bg-rw-hover">
                            <span class="w-[132px] shrink-0 text-rw-faint tabular-nums" x-show="columns.timestamp">{{ $row['ts'] }}</span>
                            <span class="w-[150px] shrink-0 truncate text-rw-subtle" x-show="columns.service">{{ $row['service'] }}</span>
                            <span class="w-[96px] shrink-0 truncate text-rw-faint" x-show="columns.replica">{{ $row['replica'] }}</span>
                            <span class="flex-1 whitespace-pre-wrap break-all {{ $row['level'] === 'error' ? '' : 'text-rw-text' }}"
                                @if ($row['level'] === 'error') style="color: var(--color-rw-danger-bright);" @endif>{{ $row['message'] }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center px-6">
                    <svg class="w-12 h-12 text-rw-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                    <div class="text-[15px] font-medium text-rw-text">
                        {{ $error ?? 'No logs in this time range' }}
                    </div>
                    <div class="text-sm text-rw-subtle">
                        {{ $error ? 'Check the server connection and try again.' : 'Logs will show up here as they are found.' }}
                    </div>
                    <button type="button" wire:click="refreshLogs" class="mt-3 rw-btn hover:rw-btn-hover rw-press rw-focus">Reload</button>
                </div>
            @endif
        </div>
    </x-railway.project-chrome>
</div>
