<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><flux:heading size="xl">Rekap Absensi Detail</flux:heading><flux:text>Tanggal pertemuan dikelompokkan per bulan dan semester.</flux:text></div>
        <div class="flex gap-2"><flux:button :href="route('reports.attendance')" wire:navigate>Rekap Ringkas</flux:button>
        @can('reports.export') <flux:button wire:click="exportExcel" variant="primary" :disabled="$filterError !== null">Ekspor Excel</flux:button> @endcan</div>
    </div>
    <div class="grid gap-4 rounded-xl border border-zinc-200 p-4 md:grid-cols-3 dark:border-zinc-700">
        <flux:select wire:model.live="academicYearId" label="Tahun ajaran"><option value="">Pilih tahun ajaran</option>@foreach ($years as $year)<option value="{{ $year->id }}">{{ $year->name }}</option>@endforeach</flux:select>
        <flux:select wire:model.live="period" label="Periode"><option value="semester">Per semester</option><option value="year">Setahun ajaran</option><option value="range">Rentang tanggal</option></flux:select>
        <flux:select wire:model.live="activityType" label="Jenis kegiatan">@foreach ($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</flux:select>
        @if ($period === 'semester')<flux:select wire:model.live="semesterId" label="Semester"><option value="">Pilih semester</option>@foreach ($semesters as $semester)<option value="{{ $semester->id }}">{{ $semester->name }}</option>@endforeach</flux:select>@endif
        @if ($period === 'range')<flux:input wire:model.live="startDate" type="date" label="Dari tanggal" /><flux:input wire:model.live="endDate" type="date" label="Sampai tanggal" />@endif
        <flux:select wire:model.live="classroomId" label="Kelas"><option value="">Semua kelas</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</flux:select>
        <flux:input wire:model.live.debounce.300ms="search" label="Cari nama atau NIS" />
    </div>
    <flux:text>H: hadir · T: terlambat · S: sakit · I: izin · A: alpa · ?: belum diabsen · -: bukan peserta. Hari tanpa sesi tidak dihitung sebagai alpa.</flux:text>
    <flux:text>% hadir = (H + T) / jumlah sesi peserta. % bobot memakai pengaturan bobot absensi sekolah. Kegiatan khusus ditampilkan terpisah dan tidak masuk faktor kehadiran latihan rutin.</flux:text>
    @if ($filterError)<flux:callout variant="warning">{{ $filterError }}</flux:callout>@else
        <flux:text>{{ $sessionCount }} sesi pada periode ini. Siswa pindah/alumni tetap ditampilkan jika memiliki penempatan pada tahun tersebut.</flux:text>
        <div class="max-h-[70vh] overflow-auto rounded-xl border border-zinc-200 dark:border-zinc-700" wire:loading.class="opacity-50">
            @include('reports.partials.attendance-detail-table')
        </div>
        @if ($students) {{ $students->links() }} @endif
    @endif
</div>
