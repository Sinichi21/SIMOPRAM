<div class="mx-auto max-w-7xl space-y-6 p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-medium text-emerald-700">Administrasi Gudep · Pembinaan</p>
            <h1 class="mt-1 text-2xl font-bold">Pembinaan SKU/SKK, Prestasi & Jurnal</h1>
            <p class="mt-1 text-sm text-zinc-500">Pemantauan capaian SKU/SKK dan prestasi. Perencanaan Tahunan–Semester–Mingguan dikelola di menu Program Kerja; agenda dan jurnal menggunakan modul Kegiatan.</p>
        </div>
        <a href="{{ route('modules.show', 'administrasi') }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">Kembali ke Portal</a>
    </div>
    @if (session('status'))
        <div role="status" class="rounded-xl border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">{{ session('status') }}</div>
    @endif
    <section class="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900 md:grid-cols-2">
        <div>
            <label for="development-gudep" class="mb-1 block text-sm font-medium">Gugusdepan</label>
            <select id="development-gudep" wire:model.live="scoutGroupId" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                <option value="">Pilih Gudep</option>
                @foreach ($gudepOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="development-year" class="mb-1 block text-sm font-medium">Tahun ajaran pembinaan</label>
            <select id="development-year" wire:model.live="academicYearId" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                <option value="">Pilih Tahun Ajaran</option>
                @foreach ($years as $option)
                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
    </section>
    @if ($gudep && $year)
        <div class="grid gap-3 sm:grid-cols-2">
            
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"><p class="text-xs text-zinc-500">Catatan SKU/SKK</p><p class="mt-1 text-2xl font-bold">{{ $competencies->count() }}</p></div>
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"><p class="text-xs text-zinc-500">Prestasi & tanda kecakapan</p><p class="mt-1 text-2xl font-bold">{{ $achievements->count() }}</p></div>
        </div>
        {{-- Legacy GudepWorkProgram data and routes are retained for compatibility.
             Planning now happens exclusively in GudepProgramPlanner. --}}
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">1. Progres SKU dan SKK</h2>
            <p class="text-sm text-zinc-500">Masukkan butir berdasarkan buku SKU/SKK resmi yang berlaku. Sistem tidak membuat daftar persyaratan secara otomatis. TKK dicatat sebagai penganugerahan pada bagian Prestasi.</p>
            @if (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.development.manage'))
                <form wire:submit="saveCompetency" class="mt-4 grid gap-3 md:grid-cols-3">
                    <label class="text-sm">Anggota
                        <select wire:model="studentId" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"><option value="">Pilih anggota</option>
                            @foreach ($members as $member)<option value="{{ $member->id }}">{{ $member->name }} · NTA {{ $member->nta ?: '-' }}</option>@endforeach
                        </select>@error('studentId')<span class="text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-sm">Jenis
                        <select wire:model="competencyCategory" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"><option value="SKU">SKU</option><option value="SKK">SKK</option></select>
                    </label>
                    <label class="text-sm">Nomor / kode butir<input wire:model="requirementCode" maxlength="80" required class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">@error('requirementCode')<span class="text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm">Nama persyaratan<input wire:model="requirementName" maxlength="255" required class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">@error('requirementName')<span class="text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm">Pembina penilai
                        <select wire:model="competencyCoachId" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"><option value="">Belum ditetapkan</option>
                            @foreach ($coaches as $coach)<option value="{{ $coach->id }}">{{ $coach->name }}</option>@endforeach
                        </select>
                    </label>
                    <label class="text-sm">Status
                        <select wire:model="competencyStatus" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                            <option value="in_progress">Proses</option><option value="passed">Tercapai</option><option value="not_passed">Perlu perbaikan</option>
                        </select>
                    </label>
                    <label class="text-sm">Tanggal penilaian<input type="date" wire:model="assessedAt" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></label>
                    <label class="text-sm md:col-span-2">Catatan<input wire:model="competencyNotes" maxlength="4000" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></label>
                    <div class="md:col-span-3"><button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm text-white">Simpan Progres</button></div>
                </form>
            @endif
            <div class="mt-4 overflow-x-auto"><table class="min-w-full text-left text-sm">
                <thead><tr class="border-b"><th class="p-2">Anggota</th><th class="p-2">Butir</th><th class="p-2">Golongan</th><th class="p-2">Status</th></tr></thead>
                <tbody>
                @forelse ($competencies as $record)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800"><td class="p-2">{{ $record->student?->name ?? '-' }} <span class="text-xs text-zinc-500">({{ $record->student?->nta ?: 'Tanpa NTA' }})</span></td>
                    <td class="p-2">{{ $record->category }} {{ $record->requirement_code }} — {{ $record->requirement_name }}</td>
                    <td class="p-2">{{ $record->scoutLevel?->name ?? '-' }}</td><td class="p-2">{{ $record->status }}</td></tr>
                @empty
                    <tr><td colspan="4" class="p-4 text-center text-zinc-500">Belum ada catatan kecakapan.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">2. Prestasi, Penganugerahan TKK & Pengembangan</h2>
            @if (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.development.manage'))
                <form wire:submit="saveAchievement" class="mt-4 grid gap-3 md:grid-cols-3">
                    <label class="text-sm">Jenis
                        <select wire:model="achievementKind" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                            <option value="achievement">Prestasi</option><option value="tkk_award">Penganugerahan TKK</option><option value="development">Pengembangan / pelatihan pembina</option>
                        </select>
                    </label>
                    <label class="text-sm">Judul<input wire:model="achievementTitle" maxlength="200" required class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">@error('achievementTitle')<span class="text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm">Tanggal<input type="date" wire:model="awardedAt" required class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></label>
                    <label class="text-sm">Anggota (opsional)
                        <select wire:model="achievementStudentId" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"><option value="">Gudep / Pembina</option>
                            @foreach ($members as $member)<option value="{{ $member->id }}">{{ $member->name }} · NTA {{ $member->nta ?: '-' }}</option>@endforeach
                        </select>@error('achievementStudentId')<span class="text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-sm">Pembina (wajib untuk pengembangan)
                        <select wire:model="achievementCoachId" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"><option value="">Pilih pembina</option>
                            @foreach ($coaches as $coach)<option value="{{ $coach->id }}">{{ $coach->name }}</option>@endforeach
                        </select>@error('achievementCoachId')<span class="text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-sm">Agenda terkait (opsional)
                        <select wire:model="achievementActivityId" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"><option value="">Tidak ditautkan</option>
                            @foreach ($activities as $activity)<option value="{{ $activity->id }}">{{ $activity->title }}</option>@endforeach
                        </select>
                    </label>
                    <label class="text-sm">Penyelenggara / penerbit<input wire:model="issuer" maxlength="180" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></label>
                    <label class="text-sm">No. piagam / sertifikat<input wire:model="referenceNumber" maxlength="100" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></label>
                    <label class="text-sm md:col-span-2">Catatan<input wire:model="achievementNotes" maxlength="4000" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></label>
                    <div class="md:col-span-3"><button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm text-white">Catat Prestasi / Pengembangan</button></div>
                </form>
            @endif
            @if (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.development.manage'))
                <form wire:submit="uploadEvidence" class="mt-4 grid gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800 md:grid-cols-3">
                    <label class="text-sm">Prestasi atau pelatihan
                        <select wire:model="evidenceAchievementId" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-900">
                            <option value="">Pilih catatan</option>
                            @foreach ($achievements as $record)
                                <option value="{{ $record->id }}">{{ $record->title }} · {{ $record->awarded_at?->format('d/m/Y') }}</option>
                            @endforeach
                        </select>
                        @error('evidenceAchievementId')<span class="text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-sm">Piagam/Sertifikat/Bukti (PDF/JPG/PNG, maks. 8 MB)
                        <input type="file" wire:model="evidenceAttachment" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm">
                        @error('evidenceAttachment')<span class="text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <div class="flex items-end">
                        <button type="submit" wire:loading.attr="disabled" wire:target="evidenceAttachment,uploadEvidence" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm text-white disabled:opacity-60">Arsipkan Bukti</button>
                    </div>
                    <p class="text-xs text-zinc-500 md:col-span-3">Bukti disimpan privat dengan checksum SHA-256. Nama bukti tercantum dalam portofolio resmi, tetapi file asli hanya dapat diunduh pengguna berizin.</p>
                </form>
            @endif
            <div class="mt-4 overflow-x-auto"><table class="min-w-full text-left text-sm">
                <thead><tr class="border-b"><th class="p-2">Tanggal</th><th class="p-2">Pencapaian</th><th class="p-2">Penerima</th><th class="p-2">Referensi</th></tr></thead><tbody>
                @forelse ($achievements as $record)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                        <td class="p-2">{{ $record->awarded_at?->format('d/m/Y') }}</td>
                        <td class="p-2">{{ $record->title }}<div class="text-xs text-zinc-500">{{ $record->kind }}</div>
                        @foreach ($record->evidences as $evidence)
                            <a class="mt-1 block text-xs font-medium text-emerald-700 hover:underline" href="{{ route('administration.development.evidence.download', $evidence->id) }}">Unduh bukti: {{ $evidence->original_filename }}</a>
                        @endforeach
                        </td>
                        <td class="p-2">{{ $record->student?->name ?? $record->coach?->name ?? 'Gudep' }}</td>
                        <td class="p-2">{{ $record->reference_number ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-4 text-center text-zinc-500">Belum ada catatan.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>

        <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-800 dark:bg-emerald-950">
            <h2 class="text-lg font-semibold">3. Jurnal Pembinaan & Portofolio Resmi</h2>
            <p class="mt-1 text-sm">Jurnal latihan tidak digandakan. Tautkan agenda pada Program Kerja, lalu isi jurnal di modul Kegiatan. Portofolio PDF mencantumkan jurnal dari agenda yang ditautkan.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                @can('journals.view')<a wire:navigate href="{{ route('journals.index') }}" class="rounded-lg border border-emerald-700 px-4 py-2 text-sm">Buka Jurnal Kegiatan</a>@endcan
                @can('activities.view')<a wire:navigate href="{{ route('activities.index') }}" class="rounded-lg border border-emerald-700 px-4 py-2 text-sm">Buka Agenda Kegiatan</a>@endcan
                @can('reports.export')<button type="button" wire:click="publishPortfolio" wire:loading.attr="disabled" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Terbitkan Portofolio Pembinaan PDF</button>@endcan
                @can('report_verifications.view')<a wire:navigate href="{{ route('reports.published-documents.index') }}" class="rounded-lg border border-emerald-700 px-4 py-2 text-sm">Dokumen Terbit</a>@endcan
            </div>
        </section>
    @else
        <p class="text-sm text-amber-700">Pilih Gudep dan tahun ajaran untuk mulai mengelola pembinaan.</p>
    @endif
</div>
