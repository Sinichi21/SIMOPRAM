<section class="space-y-4 rounded-xl border border-amber-300 p-5">
    <div><h2 class="text-xl font-semibold">Juri &amp; Peringkat Kegiatan Khusus</h2>
        <p class="mt-2 text-sm text-zinc-500">Nilai ini hanya untuk kegiatan dan tidak masuk nilai semester. Setiap juri memiliki link sendiri, aktif selama jadwal kegiatan dan tertutup setelah finalisasi. Kriteria dan peserta dikunci setelah link pertama dibuat.</p>
        <p class="mt-2 text-sm">Jadwal: {{ $assessment->activity?->start_at?->format('d-m-Y H:i') }} – {{ $assessment->activity?->end_at?->format('d-m-Y H:i') }} ({{ config('app.timezone') }})</p></div>
    @if($errors->any())<div role="alert" class="text-red-600">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if ($canManageJudges)
        <form wire:submit="invite" class="flex flex-wrap items-end gap-3">
            <label>Nama juri<input wire:model="judgeName" required maxlength="150" class="mt-1 block rounded-lg border p-2"></label>
            <button wire:loading.attr="disabled" class="rounded-lg bg-zinc-900 px-4 py-2 text-white">Buat Link Juri</button>
        </form>
        @if($invitationUrl)
            <form wire:submit="sendInvitation" class="space-y-3">
                <flux:select wire:model="messageChannel" label="Kirim undangan melalui">
                    <flux:select.option value="whatsapp">WhatsApp</flux:select.option>
                    <flux:select.option value="telegram">Telegram</flux:select.option>
                    <flux:select.option value="email">Email</flux:select.option>
                </flux:select>
                <flux:input wire:model="messageDestination" label="Nomor HP, username / chat ID Telegram, atau email" />
                <flux:button type="submit" wire:loading.attr="disabled">Kirim undangan</flux:button>
                @if(session('judge_message'))<flux:text>{{ session('judge_message') }}</flux:text>@endif
            </form>
            <div class="rounded-lg bg-amber-50 p-3 text-zinc-900"><p>Salin dan berikan link ini kepada juri. Link hanya ditampilkan saat dibuat; jika hilang, cabut akses lama dan buat link baru sebelum ada finalisasi.</p>
                <input aria-label="Link juri" readonly value="{{ $invitationUrl }}" onclick="this.select()" class="mt-2 w-full rounded border p-2"></div>
        @endif
    @endif
    <ul class="space-y-2">@foreach($assessment->judges as $judge)
        <li wire:key="judge-{{ $judge->id }}" class="flex flex-wrap items-center justify-between gap-2 border-b py-2">
            <span>{{ $judge->name }} — {{ $judge->finalized_at ? 'Final' : ($judge->revoked_at ? 'Dicabut' : (now()->gte($judge->expires_at) ? 'Kedaluwarsa, belum final' : 'Belum final')) }}</span>
            @if ($canManageJudges)
                @if(! $judge->finalized_at && ! $judge->revoked_at)<button type="button" wire:click="revoke({{ $judge->id }})" wire:confirm="Cabut akses juri ini?" class="text-red-600">Cabut Akses</button>@endif
            @endif
        </li>
    @endforeach</ul>
    <h3 class="font-semibold">{{ $isFinal ? 'Hasil Final' : 'Hasil Sementara' }} — rata-rata juri yang sudah final</h3>
    <p class="text-sm text-zinc-500">Nilai sama mendapat peringkat sama (contoh: 1, 1, 3). Draft juri tidak dihitung.</p>
    @include('landing.judge-list', ['resultJudges' => $resultJudges])
    @include('landing.ranking-table', ['rankings' => $rankings, 'showJudgeScores' => true])
    @if($canExport)
        <form action="{{ route('assessment-reports.store', $assessment->id) }}" method="POST" target="_blank" class="space-y-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            @csrf
            <h3 class="font-semibold">Cetak / export nilai</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select name="format" label="Jenis rekap">
                    <option value="complete">Rekap lengkap — semua juri, rata-rata, dan peringkat</option>
                    <option value="judges">Rekap juri — seluruh form masing-masing juri</option>
                </flux:select>
                <flux:select name="with_signatures" label="Tanda tangan">
                    <option value="1">Dengan ruang tanda tangan juri</option>
                    <option value="0">Tanpa ruang tanda tangan</option>
                </flux:select>
            </div>
            <flux:text>PDF dibuka di tab baru. Setiap cetakan memiliki tanggal terbit dan QR validasi arsip. Rekap juri menyediakan tanda tangan juri tersebut; rekap lengkap menyediakan tanda tangan seluruh juri.</flux:text>
            <flux:button type="submit" variant="primary">Terbitkan dan lihat PDF</flux:button>
        </form>
        @if($reports->isNotEmpty())
            <div class="space-y-2">
                <h3 class="font-semibold">Arsip cetakan terakhir</h3>
                @foreach($reports as $report)
                    <div wire:key="report-{{ $report->id }}"><a class="text-sm underline" href="{{ route('assessment-reports.show', $report->code) }}" target="_blank" rel="noopener noreferrer">
                        NILAI-{{ $report->issued_at->format('Ymd') }}-{{ $report->id }} · {{ $report->format === 'judges' ? 'Rekap juri' : 'Rekap lengkap' }} · {{ $report->issued_at->format('d-m-Y H:i') }}
                    </a></div>
                @endforeach
            </div>
        @endif
    @endif
</section>
