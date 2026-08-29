@props([
    'projects' => null,
    'project' => null,
    'environment' => null,
    'services' => [],
])

@php
    $projects = $projects ?? collect();

    $defaultEnvUuid = function ($p) {
        $envs = $p->environments;
        return optional($envs->firstWhere('name', 'production') ?? $envs->first())->uuid;
    };

    // Everything the palette can jump to, flattened server-side so filtering
    // stays client-side and the palette feels instant, as Railway's does.
    $items = [];

    foreach ($projects as $p) {
        $envUuid = $defaultEnvUuid($p);
        if (! $envUuid) {
            continue;
        }
        $items[] = [
            'group' => 'Projects',
            'label' => $p->name,
            'hint' => $p->description ? \Illuminate\Support\Str::limit($p->description, 48) : '',
            'href' => route('railway.canvas', ['project_uuid' => $p->uuid, 'environment_uuid' => $envUuid]),
            'icon' => 'project',
        ];
    }

    foreach ($services as $svc) {
        $items[] = [
            'group' => 'Services',
            'label' => $svc['name'],
            'hint' => $svc['subtitle'] ?? '',
            'href' => $svc['href'] ?? '',
            'icon' => $svc['icon'] ?? 'service',
        ];
    }

    if ($project && $environment) {
        $args = ['project_uuid' => $project->uuid, 'environment_uuid' => $environment->uuid];
        foreach ([
            ['Architecture', 'railway.canvas'],
            ['Observability', 'railway.observability'],
            ['Logs', 'railway.logs'],
            ['Settings', 'railway.settings'],
        ] as [$label, $routeName]) {
            $items[] = ['group' => 'Go to', 'label' => $label, 'hint' => '', 'href' => route($routeName, $args), 'icon' => 'nav'];
        }
    }

    $items[] = ['group' => 'Go to', 'label' => 'All Projects', 'hint' => '', 'href' => route('railway.projects'), 'icon' => 'nav'];
    $items[] = ['group' => 'Go to', 'label' => 'Servers', 'hint' => '', 'href' => route('server.index'), 'icon' => 'nav'];
    $items[] = ['group' => 'Go to', 'label' => 'Teams', 'hint' => '', 'href' => route('team.index'), 'icon' => 'nav'];
@endphp

<div x-data="{
        open: false,
        query: '',
        cursor: 0,
        items: @js($items),
        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (!q) return this.items;
            return this.items.filter(i => (i.label + ' ' + i.hint).toLowerCase().includes(q));
        },
        get groups() {
            const out = [];
            for (const i of this.filtered) {
                let g = out.find(x => x.name === i.group);
                if (!g) { g = { name: i.group, items: [] }; out.push(g); }
                g.items.push(i);
            }
            return out;
        },
        /* Flat order matches the rendered order so arrow keys track the eye. */
        get flat() { return this.groups.flatMap(g => g.items); },
        show() { this.open = true; this.query = ''; this.cursor = 0; $nextTick(() => $refs.input?.focus()); },
        move(step) {
            const n = this.flat.length;
            if (!n) return;
            this.cursor = (this.cursor + step + n) % n;
            $nextTick(() => $refs.list?.querySelector('[data-active=true]')?.scrollIntoView({ block: 'nearest' }));
        },
        choose() {
            const target = this.flat[this.cursor];
            if (target?.href) window.location.href = target.href;
        },
        indexOf(item) { return this.flat.indexOf(item); },
     }"
     @keydown.window.meta.k.prevent="show()"
     @keydown.window.ctrl.k.prevent="show()"
     @open-railway-palette.window="show()">

    <template x-if="open">
        <div class="fixed inset-0 z-[60] flex items-start justify-center pt-[15vh] px-4"
             style="background: rgba(0,0,0,0.5);"
             @click.self="open = false"
             @keydown.escape.window="open = false">

            <div class="rw-palette rw-palette-push max-w-[560px]" @click.stop>
                <div class="flex items-center gap-3 px-4 h-[52px] border-b" style="border-color: var(--color-rw-border);">
                    <svg class="w-4 h-4 text-rw-faint shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input x-ref="input" x-model="query" type="text" placeholder="Search projects, services and pages…"
                           class="flex-1 bg-transparent text-sm text-rw-text placeholder:text-rw-faint focus:outline-none border-0 p-0"
                           @keydown.arrow-down.prevent="move(1)"
                           @keydown.arrow-up.prevent="move(-1)"
                           @keydown.enter.prevent="choose()" />
                    <kbd class="text-[11px] text-rw-faint rounded border px-1.5 py-0.5" style="border-color: var(--color-rw-border);">esc</kbd>
                </div>

                <div class="rw-palette-list py-2" x-ref="list">
                    <template x-for="group in groups" :key="group.name">
                        <div class="mb-1">
                            <div class="rw-palette-heading" x-text="group.name"></div>
                            <template x-for="item in group.items" :key="item.group + item.label + item.href">
                                <a :href="item.href"
                                   class="rw-palette-item"
                                   :class="indexOf(item) === cursor ? 'rw-palette-item-active' : ''"
                                   :data-active="indexOf(item) === cursor"
                                   @mouseenter="cursor = indexOf(item)">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                          :style="item.icon === 'project'
                                              ? 'background: var(--color-rw-accent-hover)'
                                              : 'background: var(--color-rw-border-strong)'"></span>
                                    <span class="truncate" x-text="item.label"></span>
                                    <span class="ml-auto text-xs text-rw-faint truncate max-w-[45%]" x-text="item.hint"></span>
                                </a>
                            </template>
                        </div>
                    </template>

                    <template x-if="!flat.length">
                        <div class="px-6 py-8 text-center text-sm text-rw-subtle">No matches.</div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>
