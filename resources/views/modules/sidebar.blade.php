<flux:sidebar.nav data-navigation="workspace" data-module="{{ $workspace['slug'] }}">
    <flux:sidebar.group heading="Ruang kerja" class="grid">
        <flux:sidebar.item icon="arrow-left" :href="route('modules.portal')" wire:navigate>
            Kembali ke Portal Modul
        </flux:sidebar.item>
        <flux:sidebar.item icon="home" :href="route('dashboard')" wire:navigate>
            Dashboard Utama
        </flux:sidebar.item>
    </flux:sidebar.group>

    @if ($availableModules !== [])
        <div class="px-2">
            <flux:dropdown class="w-full">
                <flux:button size="sm" class="w-full justify-between" icon:trailing="chevron-down">
                    Pindah modul
                </flux:button>
                <flux:menu aria-label="Pindah modul">
                    @foreach ($availableModules as $module)
                        <flux:menu.item :icon="$module['icon']" :href="route('modules.show', $module['slug'])"
                            :aria-current="$module['slug'] === $workspace['slug'] ? 'true' : null" wire:navigate>
                            {{ $module['title'] }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>
        </div>
    @endif

    @if ($workspaceLinks !== [])
        <flux:sidebar.group :heading="$workspace['title']" class="grid">
            <flux:sidebar.item :icon="$workspace['icon']" :href="route('modules.show', $workspace['slug'])"
                :current="request()->routeIs('modules.show')" wire:navigate>
                Dashboard modul
            </flux:sidebar.item>
            @foreach ($workspaceLinks as $link)
                <flux:sidebar.item :href="route($link['route'], $link['parameters'])"
                    :current="\App\Support\ModuleWorkspace::isCurrent($link)"
                    class="h-auto! min-h-10 py-1! [&_[data-content]]:whitespace-normal" wire:navigate>
                    {{ $link['label'] }}
                </flux:sidebar.item>
            @endforeach
        </flux:sidebar.group>
    @else
        <flux:heading class="px-3">{{ $workspace['title'] }}</flux:heading>
    @endif
</flux:sidebar.nav>
