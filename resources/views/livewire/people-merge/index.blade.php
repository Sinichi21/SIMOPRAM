<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Penggabungan Identitas Profil</h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
            Hubungkan beberapa profil ke satu identitas global tanpa menghapus profil, akun, atau riwayat sekolah.
            Halaman ini hanya dapat digunakan oleh Super Admin.
        </p>
    </div>

    @if (session()->has('status'))
        <div class="rounded-lg border border-green-300 bg-green-50 p-4 text-sm text-green-900 dark:border-green-800 dark:bg-green-950 dark:text-green-100">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
        <strong>Perhatian:</strong> pilih hanya profil yang sudah dipastikan milik orang yang sama.
        Profil sekolah dan akun login tidak dihapus. Akun pengguna hanya ditautkan ke identitas, bukan digabung atau dihapus.
    </div>

    <div class="grid gap-5 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700 lg:grid-cols-2">
        <div class="space-y-2">
            <label for="profileType" class="block text-sm font-medium">Jenis data</label>
            <select id="profileType" wire:model.live="profileType" class="w-full rounded-lg border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900">
                <option value="coach">Profil pembina</option>
                <option value="student">Profil siswa</option>
                <option value="user">Akun pengguna</option>
            </select>
            <p class="text-xs text-zinc-500">Daftar hanya menampilkan data yang belum memiliki person_id.</p>
        </div>

        <div class="space-y-2">
            <label for="search" class="block text-sm font-medium">Cari profil</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search" placeholder="Nama, NIP/NIS, atau email" class="w-full rounded-lg border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900">
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <h2 class="font-semibold">Profil yang belum tertaut</h2>
            <p class="mt-1 text-sm text-zinc-500">Centang profil yang sudah terverifikasi sebagai orang yang sama.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 text-left dark:bg-zinc-800">
                    <tr>
                        <th class="w-12 px-4 py-3">Pilih</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Sekolah / akun</th>
                        <th class="px-4 py-3">ID</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($profiles as $profile)
                        <tr wire:key="merge-profile-{{ $profileType }}-{{ $profile->id }}">
                            <td class="px-4 py-3">
                                <input type="checkbox" wire:model.live="selectedProfileIds" value="{{ $profile->id }}" class="rounded border-zinc-300">
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $profile->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                @if ($profileType === 'user')
                                    <div>{{ $profile->email }}</div>
                                    <div class="text-xs">Role sistem: {{ $profile->system_role ?: '—' }}</div>
                                @else
                                    {{ $profile->school?->name ?? 'Sekolah tidak diketahui' }}
                                    @if ($profileType === 'student')
                                        <div class="text-xs text-zinc-500">NIS: {{ $profile->nis ?: '—' }} · NISN: {{ $profile->nisn ?: '—' }}</div>
                                    @else
                                        <div class="text-xs text-zinc-500">NIP: {{ $profile->nip ?: '—' }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-500">#{{ $profile->id }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-zinc-500">Tidak ada profil tanpa identitas untuk filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
            {{ $profiles->links() }}
            <p class="mt-2 text-xs text-zinc-500">Terpilih: {{ count($selectedProfileIds) }} profil. Pilihan dapat mencakup halaman daftar yang berbeda.</p>
        </div>
    </div>

    <form wire:submit="linkProfiles" class="space-y-5 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <div>
            <h2 class="font-semibold">Tautkan ke identitas</h2>
            <p class="mt-1 text-sm text-zinc-500">Pilih identitas yang sudah ada, atau buat identitas baru dari minimal dua profil.</p>
        </div>

        <div class="space-y-2">
            <label for="targetPersonId" class="block text-sm font-medium">Identitas tujuan</label>
            <select id="targetPersonId" wire:model.live="targetPersonId" class="w-full rounded-lg border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">Buat identitas baru</option>
                @foreach ($people as $person)
                    <option value="{{ $person->id }}">
                        {{ $person->name }} (#{{ $person->id }}) — {{ $person->users_count }} akun, {{ $person->student_profiles_count }} profil siswa, {{ $person->coach_profiles_count }} profil pembina
                    </option>
                @endforeach
            </select>
            @error('targetPersonId') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        @if (! $targetPersonId)
            <div class="space-y-2">
                <label for="newPersonName" class="block text-sm font-medium">Nama identitas baru</label>
                <input id="newPersonName" type="text" wire:model="newPersonName" maxlength="150" placeholder="Nama orang yang sudah diverifikasi" class="w-full rounded-lg border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900">
                @error('newPersonName') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        @error('selectedProfileIds') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        @error('selectedProfileIds.*') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        @error('confirmLink') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <label class="flex items-start gap-3 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
            <input type="checkbox" wire:model="confirmLink" class="mt-1 rounded border-zinc-300">
            <span>Saya sudah memverifikasi bahwa semua profil terpilih merujuk pada orang yang sama dan memahami bahwa tindakan ini mengubah person_id.</span>
        </label>

        <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-700 disabled:opacity-50 dark:bg-white dark:text-zinc-900">
            <span wire:loading.remove>Tautkan profil</span>
            <span wire:loading>Memproses...</span>
        </button>
    </form>
</div>
