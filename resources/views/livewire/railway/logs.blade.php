<div>
    <x-slot:title>{{ $project->name }} · Logs | Coolify</x-slot>

    <x-railway.project-chrome :project="$project" :environment="$environment"
        :projects="$allProjects" :environments="$allEnvironments" active="logs">

        <div class="flex flex-col h-full min-h-0">
            <div class="flex items-center gap-3 px-5 py-3 border-b" style="border-color: var(--color-rw-border);">
                <div class="min-w-0 flex-1">
                    <div class="text-[14px] font-semibold text-rw-text">Environment logs</div>
                    <div class="text-[12px] text-rw-subtle">Live container output from {{ $environment->name }}</div>
                </div>
                <span class="rw-pill">{{ $resources->count() }} {{ Illuminate\Support\Str::plural('service', $resources->count()) }}</span>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto scrollbar px-5 py-4">
                @forelse ($resources as $resource)
                    @php
                        $kind = App\Support\RailwayResourceMapper::kind($resource);
                    @endphp
                    <section class="mb-5" wire:key="railway-logs-{{ $resource->uuid }}">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-md shrink-0" style="background: var(--color-rw-elevated); border: 1px solid var(--color-rw-border);">
                                <x-railway.resource-icon :type="App\Support\RailwayResourceMapper::iconType($resource)" size="w-3.5 h-3.5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="text-[13px] font-medium text-rw-text truncate">{{ $resource->name }}</div>
                                <div class="text-[11px] text-rw-subtle">{{ ucfirst($kind) }}</div>
                            </div>
                            <x-railway.status-dot :status="$resource->status" />
                        </div>
                        <div class="rw-embed rounded-md border overflow-hidden" style="border-color: var(--color-rw-border);">
                            <livewire:project.shared.logs :resource="$resource" :key="'rw-environment-logs-'.$resource->uuid" />
                        </div>
                    </section>
                @empty
                    <div class="flex h-full flex-col items-center justify-center gap-2 text-center">
                        <svg class="w-12 h-12 text-rw-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                        <div class="text-[15px] font-medium text-rw-text">No services in this environment</div>
                        <div class="text-[13px] text-rw-subtle">Add a service to begin streaming logs.</div>
                        <a href="{{ route('railway.canvas', ['project_uuid' => $project->uuid, 'environment_uuid' => $environment->uuid]) }}" wire:navigate class="mt-2 text-[12px] text-rw-accent hover:underline">Open the canvas →</a>
                    </div>
                @endforelse
            </div>
        </div>
    </x-railway.project-chrome>
</div>
