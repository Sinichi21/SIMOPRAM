<div class="space-y-6">
    <header class="app-page-heading"><h1 class="text-2xl">Absensi kegiatan</h1><p class="mt-2">{{ $activity->title }}</p></header>
    @if(session('status'))<p role="status" class="rounded-xl bg-emerald-50 p-4 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ session('status') }}</p>@endif
    @if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="flex flex-wrap gap-3">
        <flux:button :href="route('admin.activity-participants', $activity->id)">Daftar peserta</flux:button>
        <flux:button :href="route('admin.activity-attendance.print', ['activityId'=>$activity->id,'search'=>$search,'role'=>$role,'attendance'=>$attendance,'blank'=>1])" target="_blank" rel="noopener noreferrer">Cetak form absensi</flux:button>
        <flux:button :href="route('admin.activity-attendance.print', ['activityId'=>$activity->id,'search'=>$search,'role'=>$role,'attendance'=>$attendance,'blank'=>0])" target="_blank" rel="noopener noreferrer">Cetak rekap absensi</flux:button>
    </div>
    <flux:text>Absensi per orang untuk peserta aktif yang sudah divalidasi, termasuk pembina dan cadangan. Setiap agenda/subagenda memiliki absensi sendiri. Cetakan mengikuti pencarian dan filter, mencakup semua halaman.</flux:text>
    <div class="grid gap-4 sm:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="search" label="Cari peserta" placeholder="Nama, NTA/NIP, kelompok, sekolah" maxlength="150" />
        <flux:select wire:model.live="role" label="Peran"><option value="">Semua</option><option value="student">Peserta / cadangan</option><option value="coach">Pembina</option></flux:select>
        <flux:select wire:model.live="attendance" label="Kehadiran"><option value="">Semua status</option><option value="unmarked">Belum dicatat</option>@foreach(\App\Services\GlobalActivityAttendance::STATUSES as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</flux:select>
    </div>
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm"><thead class="bg-zinc-100 dark:bg-zinc-800"><tr><th class="p-4">Nama / kelompok</th><th class="p-4">Sekolah / peran</th><th class="p-4">Kehadiran</th><th class="p-4">Catat absensi</th></tr></thead><tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
        @forelse($participants as $participant)
            <tr wire:key="attendance-{{ $participant->id }}"><td class="p-4"><p class="font-semibold">{{ $participant->name }}</p><p class="text-xs">{{ $participant->entry->name }} · {{ $participant->identifier ?: '—' }}</p></td><td class="p-4">{{ $participant->school_name }}<p class="text-xs">{{ $participant->role === 'coach' ? 'Pembina' : ($participant->is_reserve ? 'Cadangan' : 'Peserta') }}</p></td><td class="p-4">{{ \App\Services\GlobalActivityAttendance::STATUSES[$participant->attendance_status] ?? 'Belum dicatat' }}<p class="text-xs">{{ $participant->checked_in_at?->format('d-m-Y H:i') }}</p></td><td class="p-4"><div class="flex flex-wrap gap-2">
                @foreach(\App\Services\GlobalActivityAttendance::STATUSES as $value=>$label)<flux:button size="sm" :variant="$participant->attendance_status === $value ? 'primary' : 'outline'" wire:click="mark({{ $participant->id }}, '{{ $value }}')">{{ $label }}</flux:button>@endforeach
                @if($participant->attendance_status)<flux:button size="sm" wire:click="mark({{ $participant->id }}, 'unmarked')" wire:confirm="Kosongkan catatan absensi peserta ini?">Reset</flux:button>@endif
            </div></td></tr>
        @empty<tr><td colspan="4" class="p-8 text-center text-zinc-500">Belum ada peserta aktif terverifikasi yang sesuai filter.</td></tr>@endforelse
        </tbody></table>
    </div>
    {{ $participants->links() }}
</div>
