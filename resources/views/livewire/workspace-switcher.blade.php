<div wire:key="workspace-switcher">
    <flux:dropdown position="bottom" align="start">
        <button type="button" class="group flex {{ $compact ? 'w-auto rounded-md border border-zinc-200 bg-white px-2 py-1 shadow-sm dark:border-zinc-700 dark:bg-zinc-800' : 'w-full' }} items-center gap-2 rounded-lg px-2 py-1.5 text-left text-sm text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white">
            <flux:icon name="building-office" variant="mini" class="size-4 shrink-0 text-zinc-400" />
            <span class="min-w-0 flex-1 truncate font-medium">{{ $this->currentWorkspace?->name ?? 'Personal' }}</span>
            <flux:icon name="chevron-down" variant="micro" class="shrink-0 text-zinc-400" />
        </button>

        <flux:menu class="w-64">
            @if ($this->canManage)
                <flux:menu.radio.group>
                    <div class="flex items-center justify-between px-3 py-2">
                        <flux:heading size="sm">Workspaces</flux:heading>
                        <a href="{{ route('workspaces') }}" wire:navigate class="text-xs font-medium text-accent hover:underline">Manage</a>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    @forelse ($this->available as $workspace)
                        <flux:menu.radio :checked="$this->currentWorkspace?->id === $workspace->id" wire:click="switchTo({{ $workspace->id }})">
                            {{ $workspace->name }}
                        </flux:menu.radio>
                    @empty
                        <div class="px-3 py-2 text-sm text-zinc-500">No workspaces yet.</div>
                    @endforelse
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.item icon="plus" :href="route('workspaces')" wire:navigate>
                    Create workspace
                </flux:menu.item>
            @else
                <flux:menu.radio.group>
                    <div class="flex items-center justify-between px-3 py-2">
                        <flux:heading size="sm">Workspaces</flux:heading>
                        <a href="{{ route('subscription') }}" wire:navigate class="text-xs font-medium text-accent hover:underline">Upgrade</a>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.item icon="building-office" :href="route('subscription')" wire:navigate>
                    Upgrade for Workspaces
                </flux:menu.item>
            @endif
        </flux:menu>
    </flux:dropdown>
</div>
