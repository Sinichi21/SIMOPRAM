<flux:sidebar.nav>
    <flux:sidebar.group heading="Navigasi ruang kerja" class="grid">
        <flux:sidebar.item icon="arrow-left" :href="route('modules.portal')" wire:navigate>
            Kembali ke portal
        </flux:sidebar.item>
        <flux:sidebar.item icon="home" :href="route('dashboard')" wire:navigate>
            Dashboard utama
        </flux:sidebar.item>
    </flux:sidebar.group>
</flux:sidebar.nav>

@if (request()->routeIs('modules.show'))
    @php($workspace = \App\Support\ModuleWorkspace::find((string) request()->route('module')))
    @if ($workspace && \App\Support\ModuleWorkspace::permitted(auth()->user(), $workspace))
        <flux:sidebar.nav>
            <flux:sidebar.group :heading="$workspace['title']" class="grid">
                <flux:sidebar.item icon="squares-2x2" :href="route('modules.show', $workspace['slug'])" :current="request()->routeIs('modules.show')" wire:navigate>
                    Dashboard modul
                </flux:sidebar.item>
                @foreach (\App\Support\ModuleWorkspace::accessibleLinks(auth()->user(), $workspace) as $link)
                    <flux:sidebar.item :href="route($link['route'])" :current="request()->routeIs($link['route'])" wire:navigate>
                        {{ $link['label'] }}
                    </flux:sidebar.item>
                @endforeach
            </flux:sidebar.group>
        </flux:sidebar.nav>
@endif

<flux:sidebar.nav>
    <flux:sidebar.group heading="Pindah modul" class="grid">
        @if (app(\App\Support\SchoolContext::class)->hasSchool())
            @foreach (\App\Support\ModuleWorkspace::all() as $slug => $module)
                @if (\App\Support\ModuleWorkspace::permitted(auth()->user(), $module))
                    <flux:sidebar.item :href="route('modules.show', $slug)" :current="request()->routeIs('modules.show') && request()->route('module') === $slug" wire:navigate>
                        {{ $module['title'] }}
                    </flux:sidebar.item>
                @endif
            @endforeach
        @endif
    </flux:sidebar.group>
</flux:sidebar.nav>
