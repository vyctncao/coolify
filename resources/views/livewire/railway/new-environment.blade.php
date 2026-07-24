<div x-data="{ adding: false }" @click.stop>
    <button type="button" x-show="!adding" @click="adding = true; $nextTick(() => $refs.envname?.focus())"
        class="rw-menu-item hover:rw-menu-item-hover text-rw-accent w-full">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        New Environment
    </button>

    <div x-show="adding" x-cloak class="p-1.5">
        <form wire:submit="create" class="flex flex-col gap-1.5">
            <input x-ref="envname" wire:model="name" type="text" placeholder="staging" autocomplete="off"
                @keydown.escape="adding = false"
                class="w-full rounded-md border px-2.5 h-8 text-[13px] text-rw-text bg-transparent focus:outline-none placeholder:text-rw-subtle"
                style="border-color: var(--color-rw-border); background: var(--color-rw-elevated);" />
            @error('name')
                <div class="text-[11px] text-rw-danger">{{ $message }}</div>
            @enderror
            <div class="flex gap-1.5">
                <button type="submit" class="rw-btn-primary hover:rw-btn-primary-hover flex-1 !h-7 !text-[12px] justify-center">
                    <span wire:loading.remove wire:target="create">Create</span>
                    <span wire:loading wire:target="create">Creating…</span>
                </button>
                <button type="button" @click="adding = false; $wire.set('name', '')" class="rw-btn hover:rw-btn-hover !h-7 !text-[12px]">Cancel</button>
            </div>
        </form>
    </div>
</div>
