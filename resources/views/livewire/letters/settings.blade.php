<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Pengaturan Persuratan</h1>
        <p class="mt-1 text-sm text-zinc-500">
            Persuratan dipisahkan menjadi Administrasi Gudep Putra, Gudep Putri, dan Mabigus. Setiap administrasi memiliki format nomor, sequence, kop, dan penandatangan default sendiri.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 dark:border-blue-900 dark:bg-blue-950/20">
        <div class="font-semibold">Identitas Gudep tetap satu per sekolah</div>
        <p class="mt-1 text-sm">
            Nomor Gudep putra/putri, kota, dan identitas sekolah tetap dikelola di Pengaturan Dokumen. Di halaman ini hanya administrasi suratnya yang dipisahkan.
        </p>
        @if (Route::has('settings.school-documents'))
            <a href="{{ route('settings.school-documents') }}" wire:navigate class="mt-3 inline-flex rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white">
                Buka Pengaturan Dokumen
            </a>
        @endif
    </div>

    <div class="grid gap-2 sm:grid-cols-3">
        @foreach ($administrationTypes as $value => $label)
            <button
                type="button"
                wire:click="$set('administrationType', '{{ $value }}')"
                class="rounded-xl border px-4 py-3 text-left text-sm font-semibold transition
                    {{ $administrationType === $value
                        ? 'border-blue-600 bg-blue-600 text-white'
                        : 'border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <form wire:submit="save" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div>
            <h2 class="font-semibold">{{ $administrationTypes[$administrationType] ?? 'Administrasi' }}</h2>
            <p class="mt-1 text-xs text-zinc-500">Pengaturan berikut hanya berlaku untuk profil administrasi yang sedang dipilih.</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Format Nomor Surat *</label>
            <input wire:model="number_format" class="w-full rounded-lg border border-zinc-300 px-3 py-2 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-800">
            <p class="mt-1 text-xs text-zinc-500">
                Token: {sequence}, {sequence_padded}, {type}, {month}, {month_roman}, {year}, {year_short}, {gudep}, {gudep_male}, {gudep_female}, {gudep_pair}, {administration}, {administration_label}, {field}.
            </p>
            <p class="mt-1 text-xs text-zinc-500">
                {gudep} mengikuti administrasi aktif: Putra memakai nomor Gudep putra, Putri memakai nomor Gudep putri, Mabigus memakai pasangan Gudep.
            </p>
            @error('number_format') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Format Nomor Agenda Surat Masuk *</label>
            <input wire:model="agenda_format" class="w-full rounded-lg border border-zinc-300 px-3 py-2 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-800">
            @error('agenda_format') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Penandatangan Default Surat</label>
            <select wire:model="default_signatory_user_id" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                <option value="">-- Gunakan default/fallback Pengaturan Dokumen --</option>
                @foreach ($signatoryUsers as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-zinc-500">Jika dipilih, user ini otomatis menjadi penandatangan awal saat membuat surat pada administrasi ini.</p>
            @error('default_signatory_user_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Judul Kop</label>
                <input wire:model="letterhead_title" placeholder="Contoh: GERAKAN PRAMUKA" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Subjudul Kop</label>
                <input wire:model="letterhead_subtitle" placeholder="Contoh: GUGUS DEPAN ..." class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium">Alamat Kop</label>
                <textarea wire:model="letterhead_address" rows="2" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></textarea>
            </div>
        </div>

        <button class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">Simpan Profil Administrasi</button>
    </form>

    <form wire:submit="saveSequence" class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/20">
        <h2 class="font-semibold">Sinkronisasi Nomor Terakhir — {{ $administrationTypes[$administrationType] ?? '' }}</h2>
        <p class="mt-1 text-xs text-zinc-500">Sequence dipisahkan per administrasi dan per tahun sehingga nomor Putra, Putri, dan Mabigus tidak saling memengaruhi.</p>
        <div class="mt-4 grid gap-4 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Tahun</label>
                <input type="number" wire:model.live="sequenceYear" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Nomor terakhir Surat Keluar</label>
                <input type="number" wire:model="lastOutgoingNumber" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Nomor terakhir Surat Masuk</label>
                <input type="number" wire:model="lastIncomingNumber" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
            </div>
            <div class="flex items-end">
                <button class="w-full rounded-lg bg-amber-700 px-4 py-2 text-sm font-medium text-white">Set Sequence</button>
            </div>
        </div>
    </form>

    <div class="grid gap-5 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold">Jenis Surat</h2>
            <div class="mt-3 space-y-2 text-sm">@foreach ($types as $type)<div class="flex gap-3"><span class="w-8 font-mono font-semibold">{{ $type->code }}</span><span>{{ $type->name }}</span></div>@endforeach</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold">Bidang Surat</h2>
            <div class="mt-3 space-y-2 text-sm">@foreach ($fields as $field)<div class="flex gap-3"><span class="w-8 font-mono font-semibold">{{ $field->code }}</span><span>{{ $field->name }}</span></div>@endforeach</div>
        </div>
    </div>
</div>
