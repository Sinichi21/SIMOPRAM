<nav aria-label="Navigasi area" {{ $attributes->class('flex flex-wrap items-center gap-2 text-sm') }}>
    @auth
        <flux:dropdown position="bottom" align="end">
            <flux:profile
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
                circle
                aria-label="Buka menu profil"
                class="max-w-56 border border-zinc-200 bg-white pe-2 dark:border-zinc-700 dark:bg-zinc-900"
            />
            <flux:menu class="w-72 max-w-[calc(100vw-2rem)] rounded-2xl p-2 shadow-xl dark:bg-zinc-900">
                <div class="flex items-center gap-3 rounded-xl bg-zinc-50 px-3 py-4 dark:bg-zinc-800">
                    <flux:avatar :initials="auth()->user()->initials()" circle />
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-zinc-900 dark:text-zinc-100">{{ auth()->user()->name }}</p>
                        <p class="mt-1 truncate text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <p class="px-3 pb-2 pt-4 text-[10px] font-bold uppercase tracking-widest text-zinc-500">Pindah area</p>
                <flux:menu.item :href="route('home')">Global Area</flux:menu.item>
                <flux:menu.item :href="$navigationSchool ? route('schools.landing', $navigationSchool) : route('home').'#sekolah'">School Area</flux:menu.item>
                <flux:menu.item :href="route('dashboard')">{{ $dashboardLabel }}</flux:menu.item>
                <flux:menu.separator />
                @can('landing.manage')
                    <flux:menu.item :href="route('settings.landing-content')">Edit landing page</flux:menu.item>
                @endcan
                @if ($navigationSchool)
                    @can('school-landing.manage', $navigationSchool)
                        <flux:menu.item :href="route('settings.tenant-content', $navigationSchool)">Edit halaman tenant</flux:menu.item>
                    @endcan
                @endif
                <flux:menu.item :href="route('profile.edit')">Profil saya</flux:menu.item>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:menu.item as="button" type="submit" variant="danger" class="w-full">Keluar</flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    @else
        <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif class="rounded-full border border-zinc-200 px-3 py-2 font-semibold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Global Area</a>
        <a href="{{ $navigationSchool ? route('schools.landing', $navigationSchool) : route('home').'#sekolah' }}" @if(request()->routeIs('schools.*')) aria-current="page" @endif class="rounded-full border border-zinc-200 px-3 py-2 font-semibold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">School Area</a>
        <a href="{{ route('login', $navigationSchool ? ['school' => $navigationSchool->slug] : []) }}" class="rounded-full bg-emerald-800 px-4 py-2 font-bold text-white">Masuk</a>
        <a href="{{ route('register', $navigationSchool ? ['school' => $navigationSchool->id] : []) }}" class="rounded-full bg-amber-300 px-4 py-2 font-bold text-emerald-950">Daftar</a>
    @endauth
</nav>
