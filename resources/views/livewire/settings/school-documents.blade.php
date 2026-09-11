<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold">Pengaturan Dokumen Sekolah</h1>
        <p class="mt-1 text-sm text-zinc-500">
            Satu sumber pengaturan untuk laporan dan persuratan: identitas gugusdepan, penandatangan,
            jabatan, NIP/NTA, kota, serta informasi dokumen.
        </p>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-300">
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-xl border border-blue-200 bg-blue-50 p-5 dark:border-blue-900 dark:bg-blue-950/20">
        <h2 class="font-semibold text-blue-950 dark:text-blue-100">Sumber Data Penandatangan</h2>
        <p class="mt-1 text-sm text-blue-800 dark:text-blue-300">
            Pilih akun aktif sekolah atau isi data penandatangan secara manual.
            Penandatangan manual tidak memerlukan akun dan tidak memiliki permintaan persetujuan pengguna.
        </p>
    </section>

    <form wire:submit="saveProfile" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div>
            <h2 class="text-lg font-semibold">Profil Penandatangan User</h2>
            <p class="mt-1 text-sm text-zinc-500">
                Bagian ini melengkapi jabatan dan NIP/NTA untuk akun pengguna, bukan memilih posisi tanda tangan pada dokumen. Data pembina tanpa akun mengikuti Master Data Pembina.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium">User</label>
                <select wire:model.live="profileUserId" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    <option value="">-- Pilih User --</option>
                    @foreach ($signatoryUsers as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</option>
                    @endforeach
                </select>
                @error('profileUserId') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Jabatan pada Dokumen</label>
                <input type="text" wire:model="profilePosition" placeholder="Contoh: Ketua Panitia / Pembina Gugusdepan / Kepala Sekolah" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                @error('profilePosition') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>

            <div class="grid grid-cols-[110px_1fr] gap-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Jenis</label>
                    <select wire:model="profileIdentifierType" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                        <option value="NTA">NTA</option>
                        <option value="NIP">NIP</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Nomor Identitas</label>
                    <input type="text" wire:model="profileIdentifierNumber" placeholder="Nomor NTA/NIP" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                </div>
                @error('profileIdentifierType') <div class="col-span-2 text-sm text-red-600">{{ $message }}</div> @enderror
                @error('profileIdentifierNumber') <div class="col-span-2 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white">
                Simpan Profil Penandatangan
            </button>
        </div>
    </form>

    <form wire:submit="save" class="space-y-6">
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Penandatangan Default Dokumen</h2>
            <p class="mt-1 text-sm text-zinc-500">
                Tentukan siapa yang otomatis mengisi setiap posisi tanda tangan. Pilih akun, pembina dari master data tanpa akun, atau input manual.
            </p>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                @php
                    $slots = [
                        'principalSignatoryUserId' => 'Penandatangan Utama Laporan',
                        'responsibleSignatoryUserId' => 'Penandatangan Penanggung Jawab / Pelaksana',
                        'coordinatorSignatoryUserId' => 'Penandatangan Koordinator',
                        'defaultLetterSignatoryUserId' => 'Penandatangan Default Surat',
                    ];
                @endphp

                @foreach ($slots as $model => $label)
                    @php($slot = array_search($model, \App\Services\DocumentSignatoryService::SLOTS, true))
                    <div wire:key="signatory-slot-{{ $slot }}">
                        <label class="mb-1 block text-sm font-medium">{{ $label }}</label>
                        <flux:select wire:model.live="signatorySources.{{ $slot }}" label="Sumber penandatangan">
                            <option value="user">Pilih akun pengguna</option>
                            <option value="coach">Pilih Master Data Pembina (tanpa akun)</option>
                            <option value="manual">Input manual (tanpa akun)</option>
                        </flux:select>
                        @if ($signatorySources[$slot] === 'coach')
                            <flux:select wire:model="coachSignatories.{{ $slot }}" label="Pembina dari master data">
                                <option value="">Pilih pembina</option>
                                @foreach ($signatoryCoaches as $coach)
                                    <option value="{{ $coach->id }}">{{ $coach->name }} — {{ $coach->position ?: 'Pembina Pramuka' }}</option>
                                @endforeach
                            </flux:select>
                            <flux:text>Nama, jabatan, dan NTA mengikuti Master Data Pembina. Tidak memerlukan approval akun.</flux:text>
                        @elseif ($signatorySources[$slot] === 'manual')
                            <flux:input wire:model="manualSignatories.{{ $slot }}.name" label="Nama penandatangan" />
                            <flux:input wire:model="manualSignatories.{{ $slot }}.position" label="Jabatan" />
                            <flux:select wire:model="manualSignatories.{{ $slot }}.identifier_type" label="Jenis identitas"><option value="NIP">NIP</option><option value="NTA">NTA</option></flux:select>
                            <flux:input wire:model="manualSignatories.{{ $slot }}.identifier_number" label="Nomor identitas (opsional)" />
                        @else
                        <select wire:model="{{ $model }}" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">-- Tidak ditentukan --</option>
                            @foreach ($signatoryUsers as $user)
                                @php($resolved = $resolvedSignatories[$user->id] ?? null)
                                <option value="{{ $user->id }}">
                                    {{ $user->name }}
                                    @if (filled($resolved['position'] ?? null)) — {{ $resolved['position'] }} @endif
                                    @if (filled($resolved['identity'] ?? null)) — {{ $resolved['identity'] }} @endif
                                </option>
                            @endforeach
                        </select>
                        @error($model) <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-4 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 text-xs text-zinc-600 dark:border-zinc-800 dark:bg-zinc-950/40 dark:text-zinc-400">
                Slot tidak membatasi role. Label hanya menentukan posisi pada layout laporan.
                Jabatan yang tercetak tetap mengikuti profil user.
            </div>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Identitas Gugus Depan</h2>
            <p class="mt-1 text-sm text-zinc-500">
                Nilai ini juga menjadi sumber token <code>{gudep}</code> pada nomor surat.
            </p>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Nomor Gudep Putra</label>
                    <input type="text" wire:model="gudepMaleNumber" placeholder="Contoh: 04.007" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    @error('gudepMaleNumber') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Nomor Gudep Putri</label>
                    <input type="text" wire:model="gudepFemaleNumber" placeholder="Contoh: 04.008" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    @error('gudepFemaleNumber') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Jadwal Ekstrakurikuler</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Hari Rutin</label>
                    <select wire:model="extracurricularWeekday" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                        <option value="">-- Pilih Hari --</option>
                        @foreach ($weekdays as $number => $name)
                            <option value="{{ $number }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('extracurricularWeekday') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Tempat Pelaksanaan</label>
                    <input type="text" wire:model="extracurricularLocation" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Jam Mulai</label>
                    <input type="time" wire:model="extracurricularStartTime" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Jam Selesai</label>
                    <input type="time" wire:model="extracurricularEndTime" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Informasi Dokumen</h2>
            <div class="mt-5 space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Kota Penandatanganan</label>
                    <input type="text" wire:model="signingCity" placeholder="Contoh: Denpasar" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Instansi Induk</label>
                    <input type="text" wire:model="parentAgency" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Catatan Dokumen</label>
                    <textarea wire:model="documentNote" rows="4" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                </div>
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-zinc-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900">
                Simpan Pengaturan Dokumen
            </button>
        </div>
    </form>
</div>
