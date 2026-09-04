<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Laporan Pertanggungjawaban</h1>
        <p class="mt-1 text-sm text-zinc-500">Unduh LPJ kegiatan Pramuka per bulan atau per semester.</p>
    </div>
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
        Bagian keuangan belum disertakan. LPJ bulanan hanya berisi isi laporan, sedangkan LPJ semester dilengkapi satu cover dan satu lembar pengesahan.
    </div>
    <div class="grid gap-5 rounded-xl border border-zinc-200 bg-white p-5 md:grid-cols-2 dark:border-zinc-800 dark:bg-zinc-900">
        <div>
            <label class="mb-1 block text-sm font-medium">Tahun Ajaran</label>
            <select wire:model.live="academicYearId" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                <option value="">-- Pilih --</option>
                @foreach ($academicYears as $year)<option value="{{ $year->id }}">{{ $year->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Semester</label>
            <select wire:model.live="semesterId" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                <option value="">-- Pilih --</option>
                @foreach ($semesters as $semester)<option value="{{ $semester->id }}">{{ $semester->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Jenis LPJ</label>
            <select wire:model.live="periodType" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                <option value="monthly">Bulanan</option><option value="semester">Semester</option>
            </select>
        </div>
        @if ($periodType === 'monthly')
            <div>
                <label class="mb-1 block text-sm font-medium">Bulan</label>
                <select wire:model.live="month" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                    @foreach ($months as $monthNumber => $monthName)<option value="{{ $monthNumber }}">{{ $monthName }}</option>@endforeach
                </select>
            </div>
        @endif
        <div class="flex justify-end md:col-span-2">
            @can('reports.export')
                @if ($academicYearId && $semesterId && ($periodType === 'semester' || $month))
                    <a href="{{ route('reports.lpj.pdf', array_filter(['academic_year_id' => $academicYearId, 'semester_id' => $semesterId, 'period_type' => $periodType, 'month' => $periodType === 'monthly' ? $month : null])) }}" target="_blank"
                       class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900">Download LPJ PDF</a>
                @else
                    <span class="text-sm text-zinc-500">Lengkapi periode untuk mengunduh LPJ.</span>
                @endif
            @endcan
        </div>
    </div>
</div>
