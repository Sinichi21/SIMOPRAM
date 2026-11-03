<div class="mx-auto max-w-7xl space-y-6 p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Administrasi Gugusdepan</p>
            <h1 class="mt-1 text-2xl font-bold">Organisasi & Buku Induk</h1>
            <p class="mt-1 text-sm text-zinc-500">Data organisasi, pembina/Mabi, riwayat SK, satuan dan anggota yang ditautkan.</p>
        </div>
        <a href="{{ route('modules.show', 'administrasi') }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">Kembali ke dashboard</a>
    </div>

    @if (session('status'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">{{ session('status') }}</div>
    @endif

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium" for="gudep-administration-selector">Gugusdepan</label>
                <select id="gudep-administration-selector" wire:model.live="scoutGroupId" class="w-full rounded-lg border border-zinc-300 bg-white p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                    <option value="">Pilih Gugusdepan</option>
                    @foreach ($gudepOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->name }} · {{ $option->male_number ?: '-' }} / {{ $option->female_number ?: '-' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="gudep-administration-year">Tahun ajaran untuk buku induk</label>
                <select id="gudep-administration-year" wire:model.live="academicYearId" class="w-full rounded-lg border border-zinc-300 bg-white p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                    <option value="">Semua tahun ajaran</option>
                    @foreach ($years as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @unless ($gudepOptions->count())
            <p class="mt-3 text-sm text-amber-700">Belum ada Gugusdepan. Tambahkan lebih dahulu melalui <a href="{{ route('scout-groups.index') }}" class="underline">Master Gugusdepan</a>.</p>
        @endunless
    </section>

    @if ($gudep)
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs text-zinc-500">Satuan tertaut</div>
                <div class="mt-2 text-3xl font-bold">{{ $units->count() }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs text-zinc-500">Keanggotaan aktif</div>
                <div class="mt-2 text-3xl font-bold">{{ $activeMembers }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs text-zinc-500">Riwayat kepengurusan</div>
                <div class="mt-2 text-3xl font-bold">{{ $officers->count() }}</div>
            </div>
        </div>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">Profil Gugusdepan</h2>
                    <p class="text-sm text-zinc-500">Data profil disunting melalui Master Gugusdepan supaya tidak ada identitas ganda.</p>
                </div>
                <a href="{{ route('scout-groups.index') }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">Ubah Profil</a>
            </div>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-zinc-500">Nama Gudep</dt><dd class="font-medium">{{ $gudep->name }}</dd></div>
                <div><dt class="text-zinc-500">Nomor Gudep (Putra / Putri)</dt><dd>{{ $gudep->male_number ?: '-' }} / {{ $gudep->female_number ?: '-' }}</dd></div>
                <div><dt class="text-zinc-500">Kwartir Ranting</dt><dd>{{ $gudep->kwarran ?: '-' }}</dd></div>
                <div><dt class="text-zinc-500">Kwartir Cabang</dt><dd>{{ $gudep->kwarcab ?: '-' }}</dd></div>
                <div><dt class="text-zinc-500">Kamabigus</dt><dd>{{ $gudep->kamabigus_name ?: '-' }}</dd></div>
                <div><dt class="text-zinc-500">Pembina Gudep</dt><dd>{{ $gudep->head_coach_name ?: '-' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-zinc-500">Alamat Sekretariat</dt><dd class="whitespace-pre-line">{{ $gudep->secretariat_address ?: '-' }}</dd></div>
            </dl>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Riwayat Pengurus, Pembina & Majelis Pembimbing</h2>
            <p class="mt-1 text-sm text-zinc-500">Satu catatan per masa jabatan, dengan nomor dan tanggal SK. Nama Mabi/Pembina dapat diambil dari data pembina atau dimasukkan manual.</p>

            @can('gudep.manage')
                <form wire:submit="saveOfficer" class="mt-5 grid gap-3 border-t border-zinc-200 pt-5 dark:border-zinc-700 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm" for="officer-scope">Kelompok</label>
                        <select id="officer-scope" wire:model="scope" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                            <option value="gudep">Pengurus Gudep</option>
                            <option value="mabi">Majelis Pembimbing</option>
                            <option value="satuan">Pembina / Pengurus Satuan</option>
                        </select>
                        @error('scope') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm" for="officer-position">Jabatan</label>
                        <input id="officer-position" wire:model="position" placeholder="Ketua Gudep / Kamabigus" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                        @error('position') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm" for="officer-name">Nama lengkap</label>
                        <input id="officer-name" wire:model="personName" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                        @error('personName') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm" for="officer-formation">Satuan golongan (opsional, untuk pengurus satuan)</label>
                        <select id="officer-formation" wire:model="officerFormationId" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                            <option value="">Umum / Tidak terkait satuan</option>
                            @foreach ($formations as $formation)
                                <option value="{{ $formation->id }}">{{ $formation->name }}</option>
                            @endforeach
                        </select>
                        @error('officerFormationId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm" for="officer-coach">Tautkan data pembina (opsional)</label>
                        <select id="officer-coach" wire:model="coachId" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                            <option value="">Tidak ditautkan</option>
                            @foreach ($coaches as $coach)
                                <option value="{{ $coach->id }}">{{ $coach->name }}</option>
                            @endforeach
                        </select>
                        @error('coachId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm" for="officer-start">Mulai menjabat</label>
                        <input id="officer-start" type="date" wire:model="startedAt" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                        @error('startedAt') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm" for="officer-end">Akhir menjabat (opsional)</label>
                        <input id="officer-end" type="date" wire:model="endedAt" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                        @error('endedAt') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm" for="officer-sk">Nomor SK</label>
                        <input id="officer-sk" wire:model="decreeNumber" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                        @error('decreeNumber') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm" for="officer-sk-date">Tanggal SK</label>
                        <input id="officer-sk-date" type="date" wire:model="decreeDate" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                        @error('decreeDate') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm" for="officer-note">Catatan</label>
                        <input id="officer-note" wire:model="notes" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                        @error('notes') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-wrap gap-2 md:col-span-3">
                        <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">{{ $editingOfficerId ? 'Simpan Perubahan' : 'Tambah Catatan' }}</button>
                        @if ($editingOfficerId)
                            <button type="button" wire:click="resetOfficer" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm">Batal Edit</button>
                        @endif
                    </div>
                </form>
            @endcan

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800"><tr><th class="px-3 py-2">Kelompok</th><th class="px-3 py-2">Nama / Jabatan</th><th class="px-3 py-2">Masa jabatan</th><th class="px-3 py-2">SK</th><th class="px-3 py-2">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($officers as $officer)
                            <tr wire:key="officer-{{ $officer->id }}">
                                <td class="px-3 py-2">{{ strtoupper($officer->scope) }}</td>
                                <td class="px-3 py-2">{{ $officer->person_name }}<div class="text-xs text-zinc-500">{{ $officer->position }} @if ($officer->formation) · {{ $officer->formation->name }} @endif</div></td>
                                <td class="px-3 py-2">{{ $officer->started_at?->format('d/m/Y') }} — {{ $officer->ended_at?->format('d/m/Y') ?? 'Sekarang' }}</td>
                                <td class="px-3 py-2">{{ $officer->decree_number ?: '-' }}<div class="text-xs text-zinc-500">{{ $officer->decree_date?->format('d/m/Y') }}</div></td>
                                <td class="px-3 py-2">@can('gudep.manage')<button type="button" wire:click="editOfficer({{ $officer->id }})" class="font-medium text-emerald-700 hover:underline">Edit</button>@endcan</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-6 text-center text-zinc-500">Belum ada riwayat pengurus.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Satuan Golongan</h2>
            <p class="mt-1 text-sm text-zinc-500">Perindukan Siaga, Pasukan Penggalang, Ambalan Penegak, dan Racana Pandega. Berbeda dari kelompok kecil yang sebelumnya tercatat pada Master Satuan.</p>
            @can('gudep.manage')
                <form wire:submit="saveFormation" class="mt-4 grid gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm" for="formation-level">Golongan</label>
                        <select id="formation-level" wire:model="formationScoutLevelId" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                            <option value="">Pilih golongan</option>
                            @foreach ($scoutLevels as $level)
                                @if (in_array(strtoupper($level->code), ['SIAGA', 'PENGGALANG', 'PENEGAK', 'PANDEGA']))
                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('formationScoutLevelId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm" for="formation-name">Nama Satuan Golongan</label>
                        <input id="formation-name" wire:model="formationName" placeholder="Contoh: Pasukan Garuda" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                        @error('formationName') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm" for="formation-year">Tahun Ajaran</label>
                        <select id="formation-year" wire:model="formationYearId" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                            <option value="">Pilih tahun ajaran</option>
                            @foreach ($years as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </select>
                        @error('formationYearId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm" for="formation-gender">Keanggotaan</label>
                        <select id="formation-gender" wire:model="formationGender" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                            <option value="putra">Putra</option>
                            <option value="putri">Putri</option>
                            <option value="campuran">Campuran</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm" for="formation-notes">Catatan</label>
                        <input id="formation-notes" wire:model="formationNotes" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                    </div>
                    <div class="md:col-span-3">
                        <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Tambah Satuan Golongan</button>
                    </div>
                </form>
            @endcan
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 text-left dark:bg-zinc-800">
                        <tr><th class="px-3 py-2">Satuan</th><th class="px-3 py-2">Golongan</th><th class="px-3 py-2">Tahun</th><th class="px-3 py-2">Kategori</th></tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($formations as $formation)
                            <tr>
                                <td class="px-3 py-2 font-medium">{{ $formation->name }}</td>
                                <td class="px-3 py-2">{{ ucfirst($formation->formation_type) }}</td>
                                <td class="px-3 py-2">{{ $formation->academicYear?->name ?? '-' }}</td>
                                <td class="px-3 py-2">{{ ucfirst($formation->gender) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-4 text-center text-zinc-500">Belum ada satuan golongan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Satuan dalam Gugusdepan</h2>
            <p class="mt-1 text-sm text-zinc-500">Satuan lama yang belum memiliki relasi Gudep tidak diubah otomatis.</p>
            @can('gudep.manage')
                <form wire:submit="assignUnit" class="mt-4 flex flex-wrap gap-2">
                    <select wire:model="unitId" aria-label="Pilih satuan yang belum tertaut" class="min-w-64 flex-1 rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                        <option value="">Pilih satuan belum tertaut</option>
                        @foreach ($unassignedUnits as $unassigned)
                            <option value="{{ $unassigned->id }}">{{ $unassigned->name }}</option>
                        @endforeach
                    </select>
                    <select wire:model="unitFormationId" aria-label="Satuan golongan tujuan" class="min-w-64 flex-1 rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                        <option value="">Tanpa satuan golongan dulu</option>
                        @foreach ($formations as $formation)
                            <option value="{{ $formation->id }}">{{ $formation->name }} · {{ $formation->academicYear?->name ?? '-' }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Tautkan Kelompok</button>
                </form>
                @error('unitId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('unitFormationId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @endcan
            <ul class="mt-4 divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                @forelse ($units as $unit)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <span>{{ $unit->name }} <span class="text-zinc-500">({{ $unit->scoutLevel?->name ?? '-' }})</span>
                            <span class="block text-xs text-zinc-500">Satuan golongan: {{ $unit->gudepFormation?->name ?? 'Belum ditetapkan' }}</span>
                        </span>
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="text-xs text-zinc-500">{{ $unit->academicYear?->name ?? '-' }}</span>
                            @can('gudep.manage')
                                <button type="button" wire:click="detachUnit({{ $unit->id }})" wire:confirm="Lepaskan kelompok ini dari Gudep? Data anggota tidak akan dihapus." class="text-xs text-red-600 hover:underline">Lepas</button>
                            @endcan
                        </span>
                    </li>
                @empty
                    <li class="py-4 text-zinc-500">Belum ada satuan yang ditautkan.</li>
                @endforelse
            </ul>
            <a href="{{ route('scout-units.index') }}" wire:navigate class="mt-3 inline-block text-sm font-medium text-emerald-700 hover:underline">Kelola satuan dan anggota</a>
        </section>

        @can('reports.export')
            <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-800 dark:bg-emerald-950">
                <h2 class="text-lg font-semibold">Terbitkan Administrasi Resmi</h2>
                <p class="mt-1 text-sm">Kedua PDF akan terdaftar di Dokumen Terbit dan memiliki QR verifikasi. Unduh ulang PDF yang sama melalui Dokumen Terbit tanpa membuat nomor baru.</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button type="button" wire:click="publishProfile" wire:loading.attr="disabled" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Terbitkan Profil & Organisasi PDF</button>
                    <button type="button" wire:click="publishMembers" wire:loading.attr="disabled" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Terbitkan Buku Induk Anggota PDF</button>
                    <a href="{{ route('reports.published-documents.index') }}" wire:navigate class="rounded-lg border border-emerald-600 px-4 py-2 text-sm">Lihat Dokumen Terbit</a>
                </div>
                <p class="mt-3 text-xs text-zinc-500">Buku induk hanya menghitung keanggotaan satuan yang ditautkan. Identitas pribadi anggota tidak dipublikasikan pada halaman verifikasi QR.</p>
            </section>
        @endcan
    @endif
</div>
