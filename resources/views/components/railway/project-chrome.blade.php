@props([
    'project',
    'environment',
    'projects' => null,
    'environments' => null,
    'active' => 'architecture',
])

{{-- The in-project shell. Railway has no left rail: the header is the only
     chrome, and the content area runs full-bleed beneath it so the canvas
     can occupy the entire viewport. --}}
<div class="flex flex-col h-screen w-full overflow-hidden">
    <x-railway.topbar :project="$project" :environment="$environment" :projects="$projects"
        :environments="$environments" :active="$active" />
    <main class="relative flex-1 min-w-0 min-h-0 overflow-hidden">
        {{ $slot }}
    </main>

    {{-- Railway's cmdk palette, on the same Cmd/Ctrl+K binding. --}}
    @php
        $paletteServices = \App\Support\RailwayResourceMapper::resourcesFor($environment)
            ->map(fn ($r) => [
                'name' => $r->name,
                'subtitle' => \App\Support\RailwayResourceMapper::subtitle($r),
                'href' => \App\Support\RailwayResourceMapper::href($r, $project->uuid, $environment->uuid),
                'icon' => \App\Support\RailwayResourceMapper::iconType($r),
            ])->all();
    @endphp
    <x-railway.command-palette :projects="$projects" :project="$project"
        :environment="$environment" :services="$paletteServices" />
</div>
