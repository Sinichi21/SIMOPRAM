<section class="space-y-4 rounded-xl border border-amber-300 p-5">
    <div><h2 class="text-xl font-semibold">Juri &amp; Peringkat Kegiatan Khusus</h2>
        <p class="mt-2 text-sm text-zinc-500">Nilai ini hanya untuk kegiatan dan tidak masuk nilai semester. Setiap juri memiliki link sendiri, aktif selama jadwal kegiatan dan tertutup setelah finalisasi. Kriteria dan peserta dikunci setelah link pertama dibuat.</p>
        <p class="mt-2 text-sm">Jadwal: {{ $assessment->activity?->start_at?->format('d-m-Y H:i') }} – {{ $assessment->activity?->end_at?->format('d-m-Y H:i') }} ({{ config('app.timezone') }})</p></div>
    @if($errors->any())<div role="alert" class="text-red-600">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @can('activity_assessments.publish')
        <form wire:submit="invite" class="flex flex-wrap items-end gap-3">
            <label>Nama juri<input wire:model="judgeName" required maxlength="150" class="mt-1 block rounded-lg border p-2"></label>
            <button wire:loading.attr="disabled" class="rounded-lg bg-amber-700 px-4 py-2 text-white">Buat Link Juri</button>
        </form>
        @if($invitationUrl)
            <div class="rounded-lg bg-amber-50 p-3 text-zinc-900"><p>Salin dan berikan link ini kepada juri. Link hanya ditampilkan saat dibuat; jika hilang, cabut akses lama dan buat link baru sebelum ada finalisasi.</p>
                <input aria-label="Link juri" readonly value="{{ $invitationUrl }}" onclick="this.select()" class="mt-2 w-full rounded border p-2"></div>
        @endif
    @endcan
    <ul class="space-y-2">@foreach($assessment->judges as $judge)
        <li wire:key="judge-{{ $judge->id }}" class="flex flex-wrap items-center justify-between gap-2 border-b py-2">
            <span>{{ $judge->name }} — {{ $judge->finalized_at ? 'Final' : ($judge->revoked_at ? 'Dicabut' : (now()->gte($judge->expires_at) ? 'Kedaluwarsa, belum final' : 'Belum final')) }}</span>
            @can('activity_assessments.publish')
                @if(! $judge->finalized_at && ! $judge->revoked_at)<button type="button" wire:click="revoke({{ $judge->id }})" wire:confirm="Cabut akses juri ini?" class="text-red-600">Cabut Akses</button>@endif
            @endcan
        </li>
    @endforeach</ul>
    <h3 class="font-semibold">{{ $isFinal ? 'Hasil Final' : 'Hasil Sementara' }} — rata-rata juri yang sudah final</h3>
    <p class="text-sm text-zinc-500">Nilai sama mendapat peringkat sama (contoh: 1, 1, 3). Draft juri tidak dihitung.</p>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr><th class="p-2">Peringkat</th><th class="p-2">Peserta / regu</th><th class="p-2">Nilai</th></tr></thead><tbody>
        @foreach($rankings as $row)<tr wire:key="rank-{{ $row['target']->id }}" class="border-t"><td class="p-2">{{ $row['rank'] ?? '—' }}</td><td class="p-2">{{ $row['target']->student?->name ?? $row['target']->scoutUnit?->name ?? 'Peserta' }}</td><td class="p-2">{{ $row['score'] === null ? '—' : number_format($row['score'], 2) }}</td></tr>@endforeach
    </tbody></table></div>
</section>
