<div class="mx-auto max-w-7xl space-y-6 p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Administrasi Gudep · Operasional</p>
            <h1 class="mt-1 text-2xl font-bold">Buku Tamu, Rapat & Arsip Operasional</h1>
            <p class="mt-1 text-sm text-zinc-500">Buku administrasi dipisahkan menurut Gugusdepan dan tahun ajaran. Surat dan agenda lama tetap dikelola melalui modul aslinya.</p>
        </div>
        <a href="{{ route('modules.show', 'administrasi') }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">Kembali ke dashboard</a>
    </div>

    @if (session('status'))
        <div role="status" class="rounded-xl border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">{{ session('status') }}</div>
    @endif

    <section class="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900 md:grid-cols-2">
        <div><label class="mb-1 block text-sm font-medium" for="operational-gudep">Gugusdepan</label>
            <select id="operational-gudep" wire:model.live="scoutGroupId" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:bg-zinc-800">
                <option value="">Pilih Gudep</option>
                @foreach ($gudepOptions as $option)<option value="{{ $option->id }}">{{ $option->name }}</option>@endforeach
            </select>
        </div>
        <div><label class="mb-1 block text-sm font-medium" for="operational-year">Tahun Ajaran</label>
            <select id="operational-year" wire:model.live="academicYearId" class="w-full rounded-lg border border-zinc-300 p-2.5 dark:bg-zinc-800">
                <option value="">Pilih Tahun Ajaran</option>
                @foreach ($years as $option)<option value="{{ $option->id }}">{{ $option->name }}</option>@endforeach
            </select>
        </div>
    </section>

    @if ($gudep && $year)
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"><p class="text-xs text-zinc-500">Kunjungan (100 terbaru)</p><p class="mt-1 text-2xl font-bold">{{ $guests->count() }}</p></div>
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"><p class="text-xs text-zinc-500">Rapat (100 terbaru)</p><p class="mt-1 text-2xl font-bold">{{ $meetings->count() }}</p></div>
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"><p class="text-xs text-zinc-500">Surat sekolah (15 terbaru)</p><p class="mt-1 text-2xl font-bold">{{ $letters->count() }}</p></div>
        </div>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">1. Buku Tamu Gugusdepan</h2>
            <p class="text-sm text-zinc-500">Catat kunjungan, asal tamu, tujuan, dan penerima kunjungan. Nomor identitas pribadi tidak perlu dicatat.</p>
            @if (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.operations.manage'))
                <form wire:submit="saveGuest" class="mt-4 grid gap-3 md:grid-cols-3">
                    <label class="text-sm">Nama tamu <input wire:model="guestName" maxlength="150" required class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">@error('guestName')<span class="text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm">Asal / Instansi <input wire:model="guestOrganization" maxlength="180" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></label>
                    <label class="text-sm">Waktu kunjungan <input type="datetime-local" wire:model="guestVisitedAt" required class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">@error('guestVisitedAt')<span class="text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm">Tujuan <input wire:model="guestPurpose" maxlength="255" required class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">@error('guestPurpose')<span class="text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm">Diterima oleh <input wire:model="guestReceivedBy" maxlength="150" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></label>
                    <label class="text-sm">Catatan <input wire:model="guestNotes" maxlength="2000" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></label>
                    <div class="md:col-span-3"><button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Catat Kunjungan</button></div>
                </form>
            @endif
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead><tr class="border-b"><th class="p-2">Waktu</th><th class="p-2">Tamu</th><th class="p-2">Keperluan</th><th class="p-2">Penerima</th></tr></thead>
                    <tbody>
                        @forelse ($guests as $entry)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="p-2">{{ $entry->visited_at?->format('d/m/Y H:i') }}</td>
                                <td class="p-2">{{ $entry->guest_name }}<div class="text-xs text-zinc-500">{{ $entry->organization }}</div></td>
                                <td class="p-2">{{ $entry->purpose }}</td>
                                <td class="p-2">{{ $entry->received_by ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-4 text-center text-zinc-500">Belum ada kunjungan tercatat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @can('reports.export')
                <button type="button" wire:click="publishGuestBook" wire:loading.attr="disabled" class="mt-4 rounded-lg bg-emerald-700 px-4 py-2 text-sm text-white">Terbitkan Buku Tamu PDF</button>
            @endcan
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">2. Rapat, Notulen & Keputusan Gudep</h2>
            <p class="text-sm text-zinc-500">Rapat dicatat sebagai draf terlebih dahulu. Notulen difinalisasi sebelum diterbitkan. Keputusan dapat diperbarui status tindak lanjutnya.</p>
            @if (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.operations.manage'))
                <form wire:submit="saveMeeting" class="mt-4 grid gap-3 md:grid-cols-3">
                    <label class="text-sm">Nama rapat <input wire:model="meetingTitle" maxlength="200" required class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">@error('meetingTitle')<span class="text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm">Tanggal & jam <input type="datetime-local" wire:model="meetingHeldAt" required class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></label>
                    <label class="text-sm">Tempat <input wire:model="meetingLocation" maxlength="200" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></label>
                    <label class="text-sm">Pimpinan rapat <input wire:model="meetingChair" maxlength="150" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></label>
                    <label class="text-sm">Notulis <input wire:model="meetingSecretary" maxlength="150" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></label>
                    <label class="text-sm">Agenda SIMPRAM terkait
                        <select wire:model="meetingActivityId" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800">
                            <option value="">Tanpa agenda terkait</option>
                            @foreach ($activities as $activity)<option value="{{ $activity->id }}">{{ $activity->title }}</option>@endforeach
                        </select>
                        @error('meetingActivityId')<span class="text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-sm md:col-span-2">Daftar hadir ringkas <textarea wire:model="meetingAttendees" rows="2" maxlength="5000" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></textarea></label>
                    <label class="text-sm md:col-span-3">Pokok agenda <textarea wire:model="meetingAgenda" rows="2" maxlength="5000" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></textarea></label>
                    <div class="md:col-span-3"><button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm text-white">Buat Rapat (Draf)</button></div>
                </form>
            @endif

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm"><thead><tr class="border-b"><th class="p-2">Rapat</th><th class="p-2">Waktu</th><th class="p-2">Status</th><th class="p-2">Keputusan</th><th class="p-2">Aksi</th></tr></thead>
                    <tbody>
                    @forelse ($meetings as $meeting)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="meeting-{{ $meeting->id }}">
                            <td class="p-2">{{ $meeting->title }}</td>
                            <td class="p-2">{{ $meeting->held_at?->format('d/m/Y H:i') }}</td>
                            <td class="p-2">{{ $meeting->status === 'finalized' ? 'Final' : 'Draf' }}</td>
                            <td class="p-2">{{ $meeting->decisions_count }}</td>
                            <td class="p-2">
                                <button type="button" wire:click="selectMeeting({{ $meeting->id }})" class="text-emerald-700 hover:underline">Buka</button>
                                @if ($meeting->status === 'finalized' && auth()->user()?->can('reports.export'))
                                    <button type="button" wire:click="publishMeeting({{ $meeting->id }})" class="ml-3 text-emerald-700 hover:underline">Terbitkan PDF</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-4 text-center text-zinc-500">Belum ada rapat.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if ($currentMeeting)
                <div class="mt-5 space-y-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700" wire:key="meeting-editor-{{ $currentMeeting->id }}">
                    <div>
                        <h3 class="font-semibold">{{ $currentMeeting->title }}</h3>
                        <p class="text-sm text-zinc-500">{{ $currentMeeting->status === 'finalized' ? 'Notulen final, tidak dapat diubah.' : 'Notulen masih berupa draf.' }}</p>
                    </div>
                    @if ($currentMeeting->status === 'draft' && (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.operations.manage')))
                        <form wire:submit="saveMinutes" class="space-y-2">
                            <label class="block text-sm font-medium">Uraian notulen</label>
                            <textarea wire:model="meetingMinutes" rows="5" maxlength="20000" class="w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></textarea>
                            @error('meetingMinutes')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                            <button class="rounded-lg border border-emerald-700 px-3 py-2 text-sm text-emerald-700">Simpan Notulen</button>
                        </form>
                        <form wire:submit="addDecision" class="grid gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700 md:grid-cols-3">
                            <label class="text-sm md:col-span-3">Keputusan / tindak lanjut <textarea wire:model="decisionText" required rows="2" maxlength="4000" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></textarea>@error('decisionText')<span class="text-red-600">{{ $message }}</span>@enderror</label>
                            <label class="text-sm">Penanggung jawab <input wire:model="decisionResponsible" maxlength="150" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></label>
                            <label class="text-sm">Batas tindak lanjut <input type="date" wire:model="decisionDue" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:bg-zinc-800"></label>
                            <div class="flex items-end"><button class="rounded-lg bg-emerald-700 px-3 py-2 text-sm text-white">Tambah Keputusan</button></div>
                        </form>
                        <button type="button" wire:click="finalizeMeeting" wire:confirm="Finalisasi akan membekukan isi notulen. Lanjutkan?" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm text-white">Finalisasi Notulen</button>
                    @else
                        <p class="whitespace-pre-line text-sm">{{ $currentMeeting->minutes }}</p>
                    @endif
                    <h4 class="text-sm font-semibold">Keputusan & pemantauan</h4>
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($currentMeeting->decisions as $decision)
                            <li class="flex flex-wrap items-center justify-between gap-3 py-2 text-sm">
                                <span>{{ $decision->decision }}
                                    <span class="block text-xs text-zinc-500">{{ $decision->person_responsible ?: '-' }} · tenggat {{ $decision->due_at?->format('d/m/Y') ?? '-' }}</span>
                                </span>
                                @if (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.operations.manage'))
                                    <select aria-label="Status keputusan" wire:change="updateDecisionStatus({{ $decision->id }}, $event.target.value)" class="rounded-lg border border-zinc-300 p-1.5 text-sm dark:bg-zinc-800">
                                        <option value="open" @selected($decision->status === 'open')>Terbuka</option>
                                        <option value="done" @selected($decision->status === 'done')>Selesai</option>
                                    </select>
                                @else
                                    <span>{{ $decision->status }}</span>
                                @endif
                            </li>
                        @empty
                            <li class="py-2 text-sm text-zinc-500">Belum ada keputusan.</li>
                        @endforelse
                    </ul>
                </div>
            @endif
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">3. Persuratan & Arsip Tingkat Sekolah</h2>
            <p class="mt-1 text-sm text-zinc-500">Daftar di bawah menampilkan surat milik sekolah aktif, bukan hanya Gudep terpilih. Penerbitan, disposisi, dan lampiran tetap memakai modul Persuratan SIMPRAM.</p>
            <div class="mt-3 flex flex-wrap gap-2 text-sm">
                @can('letters.view')
                    <a href="{{ route('letters.incoming') }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-2">Surat Masuk</a>
                    <a href="{{ route('letters.outgoing') }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-2">Surat Keluar</a>
                @endcan
                @can('report_verifications.view')
                    <a href="{{ route('reports.published-documents.index') }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-2">Dokumen Terbit</a>
                @endcan
            </div>
            <div class="mt-4 overflow-x-auto"><table class="min-w-full text-left text-sm">
                <thead><tr class="border-b"><th class="p-2">Tanggal</th><th class="p-2">Nomor</th><th class="p-2">Perihal</th><th class="p-2">Arah</th></tr></thead><tbody>
                @forelse ($letters as $letter)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                        <td class="p-2">{{ $letter->letter_date?->format('d/m/Y') }}</td>
                        <td class="p-2">{{ $letter->letter_number ?: '-' }}</td>
                        <td class="p-2">{{ $letter->subject }}</td>
                        <td class="p-2">{{ $letter->direction === 'incoming' ? 'Masuk' : 'Keluar' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-4 text-center text-zinc-500">Belum ada surat pada periode ini.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>
    @else
        <p class="text-sm text-amber-700">Pilih Gudep dan tahun ajaran untuk mengelola administrasi operasional.</p>
    @endif
</div>
