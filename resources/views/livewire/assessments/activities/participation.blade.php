<div class="space-y-6">
    <div><h1 class="text-2xl font-semibold">Poin Keaktifan — {{ $activity->title }}</h1>
        <p class="mt-2 text-sm text-zinc-500">Nilai semester = total poin seluruh kegiatan ÷ target poin periode × 100, maksimal 100. Mengubah target akan menghitung ulang rekap seluruh siswa pada periode ini.</p></div>
    @if(session('status')) <p role="status" class="rounded-lg bg-green-50 p-4 text-green-800">{{ session('status') }}</p> @endif
    @if($errors->any()) <div role="alert" class="text-red-600">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div> @endif
    <form wire:submit="save" class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-3">
            <label>Periode penilaian<select wire:model.live="configId" class="mt-1 w-full rounded-lg border p-2">
                <option value="">Pilih konfigurasi</option>
                @foreach($configs as $option)<option value="{{ $option->id }}">{{ $option->name }}</option>@endforeach
            </select></label>
            <label>Faktor keaktifan<select wire:model="factorId" class="mt-1 w-full rounded-lg border p-2">
                <option value="">Pilih faktor</option>
                @foreach($config?->items ?? [] as $item)
                    @if($item->factor?->source_type === 'manual')<option value="{{ $item->assessment_factor_id }}">{{ $item->factor->name }}</option>@endif
                @endforeach
            </select></label>
            <label>Target poin satu periode<input type="number" min="0.01" max="1000000" step="0.01" wire:model="targetPoints" class="mt-1 w-full rounded-lg border p-2"></label>
        </div>
        <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="grid items-end gap-4 md:grid-cols-3">
                <label class="text-sm font-medium">Cari siswa
                    <input type="search" wire:model.live.debounce.300ms="search" wire:keydown.enter.prevent placeholder="Nama, NIS, atau NISN" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:border-zinc-700 dark:bg-zinc-800">
                </label>
                <label class="text-sm font-medium">Poin kegiatan ini
                    <select wire:model.live="pointsFilter" class="mt-1 w-full rounded-lg border border-zinc-300 p-2 dark:border-zinc-700 dark:bg-zinc-800">
                        <option value="">Semua poin</option>
                        <option value="zero">Poin masih nol</option>
                        <option value="positive">Sudah ada poin</option>
                    </select>
                </label>
                <div><button type="button" wire:click="resetFilters" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700">Reset filter</button></div>
            </div>
            <p role="status" class="text-sm text-zinc-500">Menampilkan {{ $students->count() }} dari {{ $studentCount }} siswa. Simpan akan menyimpan semua isian, termasuk siswa yang tersembunyi oleh filter.</p>
        </div>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead><tr><th class="p-3">Siswa</th><th class="p-3">Poin kegiatan ini</th><th class="p-3">Catatan keaktifan</th><th class="p-3">Total poin tersimpan / Nilai</th></tr></thead>
            <tbody>@forelse($students as $student)
                <tr wire:key="participation-{{ $configId }}-{{ $student->id }}" class="border-t">
                    <td class="p-3">{{ $student->name }}</td>
                    <td class="p-3"><input aria-label="Poin {{ $student->name }}" type="number" min="0" max="1000000" step="0.01" wire:model="entries.{{ $student->id }}.points" class="w-28 rounded-lg border p-2"></td>
                    <td class="p-3"><input aria-label="Catatan {{ $student->name }}" wire:model="entries.{{ $student->id }}.notes" maxlength="1000" class="w-full rounded-lg border p-2"></td>
                    <td class="p-3">{{ number_format($totals[$student->id] ?? 0, 2) }} / {{ $config?->participation_target_points ? number_format(min(100, ($totals[$student->id] ?? 0) / $config->participation_target_points * 100), 2) : '—' }}</td>
                </tr>
            @empty <tr><td colspan="4" class="p-4">{{ $studentCount ? 'Tidak ada siswa yang cocok dengan pencarian dan filter.' : 'Belum ada siswa yang sesuai dengan periode dan golongan kegiatan.' }}</td></tr> @endforelse</tbody>
        </table></div>
        @can('activity_assessments.score')<button type="submit" wire:loading.attr="disabled" @disabled(! $config) class="rounded-lg bg-zinc-900 px-4 py-2 text-white disabled:opacity-50">Simpan Poin &amp; Rekap Keaktifan</button>@endcan
    </form>
</div>
