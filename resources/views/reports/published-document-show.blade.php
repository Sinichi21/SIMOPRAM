<x-layouts::app :title="__('Detail Dokumen Terbit')">
    <div class="p-6">
        @php
            $closure = $verification->closure;
            $statusLabel = match ($status) { 'valid' => 'Valid', 'pending' => 'Menunggu persetujuan', 'superseded' => 'Versi Lama', 'revoked' => 'Dicabut', default => ucfirst($status) };
            $statusClasses = match ($status) { 'valid' => 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300', 'superseded' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300', default => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' };
        @endphp

        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <a href="{{ route('reports.published-documents.index') }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100">← Kembali ke Dokumen Terbit</a>
                    <h1 class="mt-3 text-2xl font-semibold">Detail Dokumen Terbit</h1>
                    <p class="mt-1 text-sm text-zinc-500">Identitas PDF resmi, hash integritas, sumber dokumen, dan riwayat verifikasi.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ $publicUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-700">Buka Verifikasi Publik</a>
                    @if (! $verification->isRevoked() && $verification->hasArchivedPdf())
                        @can('reports.export')
                            <a href="{{ route('reports.published-documents.download', ['code' => $verification->code]) }}" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900" target="_blank" rel="noopener noreferrer">Lihat PDF</a>
                        @endcan
                    @endif
                </div>
            </div>

            @if ($verification->isRevoked())
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
                    <div class="font-semibold">Dokumen telah dicabut</div>
                    <p class="mt-1">{{ $verification->revocation_reason ?: 'Tidak ada alasan pencabutan.' }}</p>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold">Identitas Dokumen</h2>
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClasses }}">{{ $statusLabel }}</span>
                    </div>
                    <dl class="mt-5 grid grid-cols-[140px_1fr] gap-x-4 gap-y-3 text-sm">
                        <dt class="text-zinc-500">Jenis</dt><dd class="font-medium">{{ $verification->documentTypeLabel() }}</dd>
                        <dt class="text-zinc-500">Judul</dt><dd>{{ $verification->publicTitle() }}</dd>
                        <dt class="text-zinc-500">Nomor</dt><dd>{{ $verification->document_number ?: '-' }}</dd>
                        @if ($closure)
                            <dt class="text-zinc-500">Tahun Ajaran</dt><dd>{{ $closure->academicYear?->name ?? '-' }}</dd>
                            <dt class="text-zinc-500">Semester</dt><dd>{{ $closure->semester?->name ?? '-' }}</dd>
                            <dt class="text-zinc-500">Versi Snapshot</dt><dd>v{{ $closure->version ?? '-' }}</dd>
                        @endif
                        <dt class="text-zinc-500">Diterbitkan</dt><dd>{{ $verification->issued_at?->format('d/m/Y H:i:s') ?? '-' }}</dd>
                        <dt class="text-zinc-500">Penerbit</dt><dd>{{ $verification->issuer?->name ?? '-' }}</dd>
                        @foreach ($verification->required_signatories ?? [] as $signatory)
                            <dt class="text-zinc-500">Persetujuan</dt>
                            <dd>{{ $signatory['name'] }} — {{ in_array($signatory['user_id'], $verification->pendingSignatoryIds(), true) ? 'Menunggu persetujuan' : 'Disetujui' }}</dd>
                        @endforeach
                        @foreach ($verification->signatory_approvals ?? [] as $approval)
                            <dt class="text-zinc-500">Waktu persetujuan</dt>
                            <dd>{{ $approval['name'] }} — {{ $approval['approved_at'] }}</dd>
                        @endforeach
                    </dl>
                </section>

                <section class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="font-semibold">Integritas dan Verifikasi</h2>
                    <div class="mt-5 space-y-4 text-sm">
                        <div><div class="text-xs text-zinc-500">Verification Code</div><code class="mt-1 block break-all rounded-lg bg-zinc-50 p-3 text-xs dark:bg-zinc-950">{{ $verification->code }}</code></div>
                        <div><div class="text-xs text-zinc-500">Snapshot SHA-256</div><code class="mt-1 block break-all rounded-lg bg-zinc-50 p-3 text-xs dark:bg-zinc-950">{{ $verification->snapshot_checksum ?? '-' }}</code></div>
                        <div><div class="text-xs text-zinc-500">PDF File SHA-256</div><code class="mt-1 block break-all rounded-lg bg-zinc-50 p-3 text-xs dark:bg-zinc-950">{{ $verification->file_sha256 ?? '-' }}</code></div>
                        <div><span class="text-zinc-500">Nama Arsip:</span> {{ $verification->file_name ?? '-' }}</div>
                        <div><span class="text-zinc-500">Diverifikasi:</span> {{ number_format($verification->verification_count) }} kali</div>
                    </div>
                </section>
            </div>

            <section class="rounded-xl border border-zinc-200 bg-white p-6 text-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="font-semibold">Prinsip Arsip Resmi</h2>
                <p class="mt-2 leading-6 text-zinc-500">Download ulang tidak membuat PDF baru. Sistem mengambil binary yang diarsipkan saat penerbitan pertama, memeriksa SHA-256, lalu mengirim binary yang sama sehingga verification code, QR, isi dokumen, dan hash tetap konsisten.</p>
            </section>
        </div>
    </div>
</x-layouts::app>
