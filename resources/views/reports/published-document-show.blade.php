<x-layouts::app :title="__('Detail Dokumen Terbit')">
    <div class="p-6">
        @php
            $closure = $verification->closure;

            $statusLabel = match ($status) {
                'valid' => 'Valid',
                'superseded' => 'Versi Lama',
                'revoked' => 'Dicabut',
                default => ucfirst($status),
            };

            $statusClasses = match ($status) {
                'valid' =>
                    'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
                'superseded' =>
                    'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
                default =>
                    'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
            };
        @endphp

        <div class="mx-auto max-w-6xl space-y-6">

            <div
                class="flex flex-col gap-4
                       lg:flex-row
                       lg:items-start
                       lg:justify-between"
            >
                <div>
                    <a
                        href="{{ route(
                            'reports.published-documents.index'
                        ) }}"
                        wire:navigate
                        class="text-sm text-zinc-500 hover:text-zinc-900
                               dark:hover:text-zinc-100"
                    >
                        ← Kembali ke Dokumen Terbit
                    </a>

                    <h1 class="mt-3 text-2xl font-semibold">
                        Detail Dokumen Terbit
                    </h1>

                    <p class="mt-1 text-sm text-zinc-500">
                        Identitas arsip PDF resmi dan riwayat verifikasi dokumen.
                    </p>
                </div>


                <div class="flex flex-wrap gap-2">
                    <a
                        href="{{ $publicUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-lg border border-zinc-300
                               px-4 py-2 text-sm font-medium
                               dark:border-zinc-700"
                    >
                        Buka Verifikasi Publik
                    </a>

                    @if (
                        ! $verification->isRevoked()
                        &&
                        $verification->hasArchivedPdf()
                    )
                        @can('reports.export')
                            <a
                                href="{{ route(
                                    'reports.published-documents.download',
                                    [
                                        'code' =>
                                            $verification->code,
                                    ]
                                ) }}"
                                class="rounded-lg bg-zinc-900
                                       px-4 py-2 text-sm font-medium text-white
                                       dark:bg-white dark:text-zinc-900"
                            >
                                Download Ulang PDF
                            </a>
                        @endcan
                    @endif
                </div>
            </div>


            @if ($verification->isRevoked())
                <div
                    class="rounded-xl border border-red-200
                           bg-red-50 p-4 text-sm text-red-800
                           dark:border-red-900
                           dark:bg-red-950/30
                           dark:text-red-300"
                >
                    <div class="font-semibold">
                        Dokumen telah dicabut
                    </div>

                    <p class="mt-1">
                        {{ $verification->revocation_reason
                            ?? 'Tidak ada alasan pencabutan.' }}
                    </p>
                </div>
            @elseif ($status === 'superseded')
                <div
                    class="rounded-xl border border-amber-200
                           bg-amber-50 p-4 text-sm text-amber-800
                           dark:border-amber-900
                           dark:bg-amber-950/30
                           dark:text-amber-300"
                >
                    Dokumen ini merupakan versi resmi lama karena semester
                    pernah dibuka kembali setelah snapshot ini diterbitkan.
                </div>
            @endif


            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div
                    class="rounded-xl border border-zinc-200
                           bg-white p-5 dark:border-zinc-800
                           dark:bg-zinc-900"
                >
                    <div class="text-xs text-zinc-500">
                        Status
                    </div>

                    <div class="mt-2">
                        <span
                            class="inline-flex rounded-full px-2.5 py-1
                                   text-xs font-medium {{ $statusClasses }}"
                        >
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-zinc-200
                           bg-white p-5 dark:border-zinc-800
                           dark:bg-zinc-900"
                >
                    <div class="text-xs text-zinc-500">
                        Versi Snapshot
                    </div>

                    <div class="mt-2 text-xl font-semibold">
                        v{{ $closure?->version ?? '-' }}
                    </div>
                </div>

                <div
                    class="rounded-xl border border-zinc-200
                           bg-white p-5 dark:border-zinc-800
                           dark:bg-zinc-900"
                >
                    <div class="text-xs text-zinc-500">
                        Total Verifikasi QR
                    </div>

                    <div class="mt-2 text-xl font-semibold">
                        {{ number_format(
                            $verification->verification_count
                        ) }}
                    </div>
                </div>

                <div
                    class="rounded-xl border border-zinc-200
                           bg-white p-5 dark:border-zinc-800
                           dark:bg-zinc-900"
                >
                    <div class="text-xs text-zinc-500">
                        Ukuran Arsip PDF
                    </div>

                    <div class="mt-2 text-xl font-semibold">
                        @if ($verification->file_size)
                            {{ number_format(
                                $verification->file_size / 1024,
                                2,
                                ',',
                                '.'
                            ) }} KB
                        @else
                            -
                        @endif
                    </div>
                </div>
            </div>


            <section
                class="rounded-xl border border-zinc-200
                       bg-white p-6 dark:border-zinc-800
                       dark:bg-zinc-900"
            >
                <h2 class="font-semibold">
                    Informasi Dokumen
                </h2>

                <dl class="mt-5 grid gap-5 md:grid-cols-2">
                    <div>
                        <dt class="text-xs text-zinc-500">
                            Sekolah
                        </dt>

                        <dd class="mt-1 font-medium">
                            {{ $verification->school?->name ?? '-' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-zinc-500">
                            Jenis Dokumen
                        </dt>

                        <dd class="mt-1 font-medium">
                            {{ $verification->document_type === 'grades'
                                ? 'Rekap Nilai'
                                : ucfirst(
                                    $verification->document_type
                                ) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-zinc-500">
                            Tahun Ajaran
                        </dt>

                        <dd class="mt-1 font-medium">
                            {{ $closure?->academicYear?->name ?? '-' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-zinc-500">
                            Semester
                        </dt>

                        <dd class="mt-1 font-medium">
                            {{ $closure?->semester?->name ?? '-' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-zinc-500">
                            Diterbitkan
                        </dt>

                        <dd class="mt-1 font-medium">
                            {{ $verification->issued_at?->format(
                                'd/m/Y H:i:s'
                            ) ?? '-' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-zinc-500">
                            Penerbit
                        </dt>

                        <dd class="mt-1 font-medium">
                            {{ $verification->issuer?->name ?? 'Sistem' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-zinc-500">
                            Snapshot Dikunci
                        </dt>

                        <dd class="mt-1 font-medium">
                            {{ $closure?->locked_at?->format(
                                'd/m/Y H:i:s'
                            ) ?? '-' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-zinc-500">
                            Terakhir Diverifikasi
                        </dt>

                        <dd class="mt-1 font-medium">
                            {{ $verification->last_verified_at?->format(
                                'd/m/Y H:i:s'
                            ) ?? 'Belum pernah' }}
                        </dd>
                    </div>
                </dl>
            </section>


            <section
                class="rounded-xl border border-zinc-200
                       bg-white p-6 dark:border-zinc-800
                       dark:bg-zinc-900"
            >
                <h2 class="font-semibold">
                    Integritas dan Identitas
                </h2>

                <div class="mt-5 space-y-5">
                    <div>
                        <div class="text-xs text-zinc-500">
                            Verification Code
                        </div>

                        <code
                            class="mt-1 block break-all rounded-lg
                                   bg-zinc-50 p-3 text-xs
                                   dark:bg-zinc-950"
                        >
                            {{ $verification->code }}
                        </code>
                    </div>

                    <div>
                        <div class="text-xs text-zinc-500">
                            Snapshot SHA-256
                        </div>

                        <code
                            class="mt-1 block break-all rounded-lg
                                   bg-zinc-50 p-3 text-xs
                                   dark:bg-zinc-950"
                        >
                            {{ $verification->snapshot_checksum ?? '-' }}
                        </code>
                    </div>

                    <div>
                        <div class="text-xs text-zinc-500">
                            PDF File SHA-256
                        </div>

                        <code
                            class="mt-1 block break-all rounded-lg
                                   bg-zinc-50 p-3 text-xs
                                   dark:bg-zinc-950"
                        >
                            {{ $verification->file_sha256 ?? '-' }}
                        </code>
                    </div>

                    <div>
                        <div class="text-xs text-zinc-500">
                            Nama Arsip
                        </div>

                        <div class="mt-1 font-medium">
                            {{ $verification->file_name ?? '-' }}
                        </div>
                    </div>
                </div>
            </section>


            <section
                class="rounded-xl border border-zinc-200
                       bg-white p-6 dark:border-zinc-800
                       dark:bg-zinc-900"
            >
                <h2 class="font-semibold">
                    Prinsip Download Ulang
                </h2>

                <p class="mt-2 text-sm leading-6 text-zinc-500">
                    Tombol Download Ulang tidak menghitung nilai dan tidak
                    membuat PDF baru. Sistem mengambil binary PDF yang
                    diarsipkan saat penerbitan pertama, memeriksa SHA-256 file,
                    lalu mengirim binary yang sama. Karena itu verification
                    code, QR, isi dokumen, dan hash file tetap sama.
                </p>
            </section>

        </div>
    </div>
</x-layouts::app>
