<div class="space-y-6 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
    <flux:heading size="lg">Status Keanggotaan dan Transfer Sekolah</flux:heading>
    @if (session('lifecycle')) <flux:callout variant="success">{{ session('lifecycle') }}</flux:callout> @endif
    <flux:error name="membership" /><flux:error name="transfer" />
    <form wire:submit="apply" class="space-y-4">
        <flux:select wire:model="userId" label="Pengguna"><option value="">Pilih pengguna</option>
            @foreach ($users as $user)<option value="{{ $user->id }}" wire:key="lifecycle-user-{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</option>@endforeach
        </flux:select>
        <flux:select wire:model.live="action" label="Tindakan">
            <option value="inactive">Nonaktif di sekolah ini (tetap dapat login)</option>
            <option value="retired">Pensiun / berhenti di sekolah ini</option>
            <option value="active">Aktifkan keanggotaan sekolah ini</option>
            <option value="disable">Nonaktif total akun (blokir login)</option>
            <option value="enable">Aktifkan kembali login akun</option>
            <option value="transfer">Ajukan pindah sekolah</option>
        </flux:select>
        <flux:text>Riwayat tetap tersimpan. Nonaktif total akun yang dipakai di sekolah lain hanya dapat diubah super admin. Akun admin sekolah dikelola oleh super admin.</flux:text>
        @if ($action === 'transfer')
            <flux:select wire:model="targetSchoolId" label="Sekolah tujuan"><option value="">Pilih sekolah</option>
                @foreach ($schools as $school)<option value="{{ $school->id }}" wire:key="transfer-school-{{ $school->id }}">{{ $school->name }}</option>@endforeach
            </flux:select>
            <flux:textarea wire:model="reason" label="Alasan pindah" required />
        @endif
        <flux:button type="submit" variant="primary" wire:confirm="Terapkan tindakan yang dipilih pada pengguna ini?">Terapkan</flux:button>
    </form>
    <flux:separator />
    <flux:heading>Pengajuan Transfer dan Riwayat Terbaru</flux:heading>
    <form wire:submit="requestStudent">
        <flux:heading>Ajukan Transfer Siswa ke Sekolah Lain</flux:heading>
        <flux:text>Pilih data siswa yang sudah terdaftar. Tidak perlu membuat akun atau mengisi ulang biodata siswa.</flux:text>
        <flux:select wire:model="studentId" label="Siswa"><option value="">Pilih siswa</option>
            @foreach ($students as $student)<option value="{{ $student->id }}" wire:key="transfer-student-{{ $student->id }}">{{ $student->name }} — {{ $student->nisn ?: $student->nis ?: 'Tanpa NISN / NIS' }}</option>@endforeach
        </flux:select>
        <flux:select wire:model="studentTargetSchoolId" label="Sekolah tujuan"><option value="">Pilih sekolah tujuan</option>
            @foreach ($schools as $school)<option value="{{ $school->id }}" wire:key="student-target-{{ $school->id }}">{{ $school->name }}</option>@endforeach
        </flux:select>
        <flux:textarea wire:model="studentTransferReason" label="Alasan transfer siswa" required />
        <flux:button type="submit" variant="primary" wire:confirm="Ajukan perpindahan siswa ini ke sekolah tujuan?">Ajukan Transfer Siswa</flux:button>
    </form>
    <form wire:submit="requestIncoming" class="space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        <flux:heading>Usulkan Siswa Masuk dari Sekolah Lain</flux:heading>
        <flux:text>Sekolah aktif menjadi sekolah tujuan. Isi NISN dari data siswa di sekolah asal, dengan atau tanpa akun. Jika NISN belum tersedia, admin sekolah asal dapat mengajukan melalui pilihan siswa. Transfer berlaku setelah disetujui admin sekolah asal.</flux:text>
        <flux:select wire:model="sourceSchoolId" label="Sekolah asal"><option value="">Pilih sekolah asal</option>
            @foreach ($schools as $school)<option value="{{ $school->id }}" wire:key="incoming-school-{{ $school->id }}">{{ $school->name }}</option>@endforeach
        </flux:select>
        <flux:input wire:model="nisn" label="NISN siswa" required maxlength="30" />
        <div class="grid gap-3 md:grid-cols-2">
            <flux:select wire:model="incomingYearId" label="Tahun ajaran tujuan"><option value="">Pilih tahun</option>@foreach ($years as $year)<option value="{{ $year->id }}" wire:key="incoming-year-{{ $year->id }}">{{ $year->name }}</option>@endforeach</flux:select>
            <flux:select wire:model="incomingClassroomId" label="Kelas tujuan"><option value="">Pilih kelas</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}" wire:key="incoming-class-{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</flux:select>
        </div>
        <flux:textarea wire:model="incomingReason" label="Alasan pengajuan" required />
        <flux:button type="submit" variant="primary" wire:confirm="Ajukan siswa ini masuk ke sekolah aktif dengan penempatan yang dipilih?">Ajukan Siswa Masuk</flux:button>
    </form>
    <flux:text>Untuk menerima siswa, pilih tahun ajaran dan kelas penerima. Penerimaan memindahkan keanggotaan aktif; riwayat sekolah asal tetap tersimpan.</flux:text>
    <div class="grid gap-3 md:grid-cols-2">
        <flux:select wire:model="yearId" label="Tahun ajaran penerima"><option value="">Pilih tahun</option>@foreach ($years as $year)<option value="{{ $year->id }}" wire:key="receive-year-{{ $year->id }}">{{ $year->name }}</option>@endforeach</flux:select>
        <flux:select wire:model="classroomId" label="Kelas penerima"><option value="">Pilih kelas</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}" wire:key="receive-class-{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</flux:select>
    </div>
    @forelse ($transfers as $transfer)
        <div wire:key="transfer-{{ $transfer->id }}" class="space-y-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
            <flux:heading>{{ $transfer->student?->name ?? $transfer->user?->name ?? 'Siswa' }}</flux:heading>
            <flux:text>{{ $transfer->fromSchool->name }} → {{ $transfer->toSchool->name }} — {{ ['pending' => 'Menunggu', 'accepted' => 'Diterima', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'][$transfer->status] }}</flux:text>
            <flux:text>{{ $transfer->reason }}</flux:text>
            @if ($transfer->initiated_by_destination)
                <flux:text>Diajukan sekolah tujuan. Persetujuan oleh sekolah asal. Penempatan: {{ $transfer->targetYear?->name ?? 'Tahun tidak tersedia' }} / {{ $transfer->targetClassroom?->name ?? 'Kelas tidak tersedia' }}.</flux:text>
            @endif
            @if ($transfer->status === 'pending')
                <div class="flex gap-2">
                @if ($transfer->approvingSchoolId() === $schoolId)
                    <flux:error name="transfer" />
                    <flux:error name="yearId" />
                    <flux:error name="classroomId" />
                    <flux:button size="sm" variant="primary" wire:click="resolve({{ $transfer->id }}, 'accepted')" wire:confirm="Setujui transfer dan pindahkan keanggotaan ke sekolah tujuan?">Setujui Transfer</flux:button>
                    <flux:button size="sm" wire:click="resolve({{ $transfer->id }}, 'rejected')" wire:confirm="Tolak pengajuan ini?">Tolak</flux:button>
                @else
                    <flux:button size="sm" wire:click="resolve({{ $transfer->id }}, 'cancelled')" wire:confirm="Batalkan pengajuan ini?">Batalkan</flux:button>
                @endif
                </div>
            @endif
        </div>
    @empty <flux:text>Belum ada pengajuan transfer.</flux:text> @endforelse
</div>
