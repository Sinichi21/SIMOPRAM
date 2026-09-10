<div class="space-y-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
    <flux:heading size="lg">Kenaikan Kelas dan Kelulusan Massal</flux:heading>
    <flux:text>Pilih kelas asal, periksa siswa, lalu tentukan kelas tahun berikutnya. Siswa yang tinggal kelas diproses terpisah. Kelas akhir dapat diluluskan menjadi alumni tanpa memblokir login.</flux:text>
    @if (session('progression')) <flux:callout variant="success">{{ session('progression') }}</flux:callout> @endif
    <form wire:submit="process" class="space-y-4">
        <div class="grid gap-3 md:grid-cols-2">
            <flux:select wire:model.live="sourceYearId" label="Tahun ajaran asal"><option value="">Pilih tahun</option>@foreach ($years as $year)<option value="{{ $year->id }}" wire:key="source-year-{{ $year->id }}">{{ $year->name }}</option>@endforeach</flux:select>
            <flux:select wire:model.live="sourceClassId" label="Kelas asal"><option value="">Pilih kelas</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}" wire:key="source-class-{{ $classroom->id }}">{{ $classroom->name }} (tingkat {{ $classroom->grade }})</option>@endforeach</flux:select>
        </div>
        <flux:select wire:model.live="action" label="Tindakan"><option value="promote">Naik kelas</option><option value="repeat">Tinggal kelas di tahun baru</option><option value="graduate">Lulus / alumni kelas akhir</option></flux:select>
        @if ($action !== 'graduate')
            <div class="grid gap-3 md:grid-cols-2">
                <flux:select wire:model="targetYearId" label="Tahun ajaran tujuan"><option value="">Pilih tahun baru</option>@foreach ($years as $year)<option value="{{ $year->id }}" wire:key="target-year-{{ $year->id }}">{{ $year->name }}</option>@endforeach</flux:select>
                <flux:select wire:model="targetClassId" label="Kelas tujuan"><option value="">Pilih kelas</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}" wire:key="target-class-{{ $classroom->id }}">{{ $classroom->name }} (tingkat {{ $classroom->grade }})</option>@endforeach</flux:select>
            </div>
        @endif
        <flux:button type="button" size="sm" wire:click="selectAll">Pilih semua yang ditampilkan</flux:button>
        <flux:text>{{ count($studentIds) }} siswa dipilih. Maksimal 500 siswa per proses.</flux:text>
        <flux:error name="studentIds" />
        <div class="max-h-64 space-y-2 overflow-y-auto">
            @forelse ($students as $student)
                <flux:checkbox wire:model.live="studentIds" :value="(string) $student->id" :label="$student->name.' — '.($student->nis ?: 'Tanpa NIS')" wire:key="progression-student-{{ $student->id }}" />
            @empty <flux:text>Pilih tahun dan kelas asal yang memiliki siswa aktif.</flux:text> @endforelse
        </div>
        <flux:button type="submit" variant="primary" wire:confirm="Proses siswa terpilih? Periksa tahun, kelas, dan tindakan sebelum melanjutkan. Penempatan tahun asal akan ditutup.">Proses Siswa Terpilih</flux:button>
    </form>
</div>
