<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold">Pengaturan Dokumen Sekolah</h1>
        <p class="mt-1 text-sm text-zinc-500">
            Informasi ini digunakan untuk kop, lembar pengesahan, jadwal rutin, dan LPJ Pramuka.
        </p>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-300">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Pejabat Penandatangan</h2>
            <p class="mt-1 text-sm text-zinc-500">Digunakan pada data kegiatan, lembar pengesahan, dan tanda tangan laporan bulanan.</p>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Nama Kepala Sekolah</label>
                    <input type="text" wire:model="principalName" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    @error('principalName') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">NIP Kepala Sekolah</label>
                    <input type="text" wire:model="principalNip" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    @error('principalNip') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Nama Koordinator Ekstra</label>
                    <input type="text" wire:model="coordinatorName" placeholder="Contoh: Ida Ayu ..." class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    @error('coordinatorName') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">NIP Koordinator Ekstra</label>
                    <input type="text" wire:model="coordinatorNip" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    @error('coordinatorNip') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Pembina Penanggung Jawab</h2>
            <p class="mt-1 text-sm text-zinc-500">Nama, nomor HP, dan NTA pembina diambil dari data pembina yang dipilih. Pada model Coach saat ini NTA disimpan pada field nip.</p>

            <div class="mt-5">
                <label class="mb-1 block text-sm font-medium">Pembina Pramuka</label>
                <select wire:model="responsibleCoachId" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    <option value="">-- Pilih Pembina --</option>
                    @foreach ($coaches as $coach)
                        <option value="{{ $coach->id }}">
                            {{ $coach->name }}{{ $coach->nip ? ' - NTA '.$coach->nip : '' }}
                        </option>
                    @endforeach
                </select>
                @error('responsibleCoachId') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Identitas Gugus Depan</h2>
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
            <p class="mt-1 text-sm text-zinc-500">Dipakai untuk menyusun tanggal laporan dan kolom absensi secara otomatis, termasuk tanggal tanpa kegiatan.</p>

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
                    <input type="text" wire:model="extracurricularLocation" placeholder="Contoh: Lapangan sekolah" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    @error('extracurricularLocation') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Jam Mulai</label>
                    <input type="time" wire:model="extracurricularStartTime" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    @error('extracurricularStartTime') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Jam Selesai</label>
                    <input type="time" wire:model="extracurricularEndTime" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    @error('extracurricularEndTime') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Informasi Dokumen</h2>
            <div class="mt-5 space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Kota Penandatanganan</label>
                    <input type="text" wire:model="signingCity" placeholder="Contoh: Denpasar" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    @error('signingCity') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Instansi Induk</label>
                    <input type="text" wire:model="parentAgency" placeholder="Contoh: Dinas Pendidikan Kepemudaan dan Olahraga Kota Denpasar" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                    @error('parentAgency') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Catatan Dokumen</label>
                    <textarea wire:model="documentNote" rows="4" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                    @error('documentNote') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
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
