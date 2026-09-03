<div class="space-y-6">

    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">
                Dokumen Terbit
            </h1>

            <p class="mt-1 max-w-3xl text-sm leading-6 text-zinc-500">
                Kelola PDF resmi yang pernah diterbitkan dari snapshot semester,
                pantau jumlah verifikasi QR, dan cabut dokumen bila diperlukan
                tanpa menghapus histori.
            </p>
        </div>
    </div>


    @if (session('status'))
        <div
            class="rounded-xl border border-green-200 bg-green-50 px-4 py-3
                   text-sm text-green-700 dark:border-green-900
                   dark:bg-green-950/40 dark:text-green-300"
        >
            {{ session('status') }}
        </div>
    @endif


    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @php
            $cards = [
                ['label' => 'Total Dokumen', 'value' => $statistics['total']],
                ['label' => 'Valid', 'value' => $statistics['valid']],
                ['label' => 'Versi Lama', 'value' => $statistics['superseded']],
                ['label' => 'Dicabut', 'value' => $statistics['revoked']],
                [
                    'label' => 'Total Scan / Verifikasi',
                    'value' => $statistics['verification_count'],
                ],
            ];
        @endphp

        @foreach ($cards as $card)
            <div
                class="rounded-xl border border-zinc-200 bg-white p-5
                       dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="text-xs text-zinc-500">
                    {{ $card['label'] }}
                </div>

                <div class="mt-2 text-2xl font-semibold">
                    {{ number_format($card['value']) }}
                </div>
            </div>
        @endforeach
    </div>


    <section
        class="rounded-xl border border-zinc-200 bg-white p-5
               dark:border-zinc-800 dark:bg-zinc-900"
    >
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div>
                <label class="mb-1 block text-sm font-medium">
                    Cari
                </label>

                <input
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    class="w-full rounded-lg border border-zinc-300 bg-white
                           px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    placeholder="Kode, checksum, penerbit..."
                >
            </div>


            <div>
                <label class="mb-1 block text-sm font-medium">
                    Status
                </label>

                <select
                    wire:model.live="status"
                    class="w-full rounded-lg border border-zinc-300 bg-white
                           px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                >
                    <option value="">
                        Semua Status
                    </option>

                    <option value="valid">
                        Valid
                    </option>

                    <option value="superseded">
                        Versi Lama
                    </option>

                    <option value="revoked">
                        Dicabut
                    </option>
                </select>
            </div>


            <div>
                <label class="mb-1 block text-sm font-medium">
                    Jenis Dokumen
                </label>

                <select
                    wire:model.live="documentType"
                    class="w-full rounded-lg border border-zinc-300 bg-white
                           px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                >
                    <option value="">
                        Semua Jenis
                    </option>

                    @foreach ($documentTypes as $type)
                        <option value="{{ $type }}">
                            {{ $type === 'grades'
                                ? 'Rekap Nilai'
                                : ucfirst($type) }}
                        </option>
                    @endforeach
                </select>
            </div>


            <div>
                <label class="mb-1 block text-sm font-medium">
                    Dari Tanggal
                </label>

                <input
                    type="date"
                    wire:model.live="dateFrom"
                    class="w-full rounded-lg border border-zinc-300 bg-white
                           px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                >
            </div>


            <div>
                <label class="mb-1 block text-sm font-medium">
                    Sampai Tanggal
                </label>

                <input
                    type="date"
                    wire:model.live="dateTo"
                    class="w-full rounded-lg border border-zinc-300 bg-white
                           px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                >
            </div>
        </div>


        <div class="mt-4">
            <button
                type="button"
                wire:click="resetFilters"
                class="rounded-lg border border-zinc-300 px-4 py-2 text-sm
                       dark:border-zinc-700"
            >
                Reset Filter
            </button>
        </div>
    </section>


    <section
        class="overflow-hidden rounded-xl border border-zinc-200 bg-white
               dark:border-zinc-800 dark:bg-zinc-900"
    >
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1200px] text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-950">
                    <tr>
                        <th class="px-4 py-3 text-left">Diterbitkan</th>
                        <th class="px-4 py-3 text-left">Dokumen</th>
                        <th class="px-4 py-3 text-left">Periode</th>
                        <th class="px-4 py-3 text-center">Versi</th>
                        <th class="px-4 py-3 text-left">Kode Verifikasi</th>
                        <th class="px-4 py-3 text-center">Scan</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($documents as $document)
                        @php
                            $publicStatus = $document->publicStatus();
                            $closure = $document->closure;
                        @endphp

                        <tr class="align-top">
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="font-medium">
                                    {{ $document->issued_at?->format('d/m/Y H:i') }}
                                </div>

                                <div class="mt-1 text-xs text-zinc-500">
                                    {{ $document->issuer?->name ?? 'Sistem' }}
                                </div>
                            </td>


                            <td class="px-4 py-4">
                                <div class="font-medium">
                                    {{ $document->document_type === 'grades'
                                        ? 'Rekap Nilai'
                                        : ucfirst($document->document_type) }}
                                </div>

                                <div
                                    class="mt-1 max-w-[180px] truncate font-mono
                                           text-xs text-zinc-500"
                                    title="{{ $document->snapshot_checksum }}"
                                >
                                    {{ $document->snapshot_checksum }}
                                </div>
                            </td>


                            <td class="px-4 py-4">
                                <div>
                                    {{ $closure?->academicYear?->name ?? '-' }}
                                </div>

                                <div class="mt-1 text-xs text-zinc-500">
                                    Semester
                                    {{ $closure?->semester?->name ?? '-' }}
                                </div>
                            </td>


                            <td class="px-4 py-4 text-center font-medium">
                                v{{ $closure?->version ?? '-' }}
                            </td>


                            <td class="px-4 py-4">
                                <code
                                    class="block max-w-[220px] truncate text-xs"
                                    title="{{ $document->code }}"
                                >
                                    {{ $document->code }}
                                </code>

                                @if ($document->last_verified_at)
                                    <div class="mt-1 text-xs text-zinc-500">
                                        Terakhir:
                                        {{ $document->last_verified_at->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                            </td>


                            <td class="px-4 py-4 text-center">
                                {{ number_format($document->verification_count) }}
                            </td>


                            <td class="px-4 py-4">
                                @if ($publicStatus === 'valid')
                                    <span
                                        class="inline-flex rounded-full bg-green-100 px-2.5 py-1
                                               text-xs font-medium text-green-700
                                               dark:bg-green-950 dark:text-green-300"
                                    >
                                        Valid
                                    </span>
                                @elseif ($publicStatus === 'superseded')
                                    <span
                                        class="inline-flex rounded-full bg-amber-100 px-2.5 py-1
                                               text-xs font-medium text-amber-700
                                               dark:bg-amber-950 dark:text-amber-300"
                                    >
                                        Versi Lama
                                    </span>
                                @else
                                    <span
                                        class="inline-flex rounded-full bg-red-100 px-2.5 py-1
                                               text-xs font-medium text-red-700
                                               dark:bg-red-950 dark:text-red-300"
                                    >
                                        Dicabut
                                    </span>

                                    @if ($document->revocation_reason)
                                        <div
                                            class="mt-2 max-w-[240px] text-xs leading-5
                                                   text-zinc-500"
                                        >
                                            {{ $document->revocation_reason }}
                                        </div>
                                    @endif
                                @endif
                            </td>


                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a
                                        href="{{ route(
                                            'reports.published-documents.show',
                                            [
                                                'code' =>
                                                    $document->code,
                                            ]
                                        ) }}"
                                        wire:navigate
                                        class="rounded-lg border border-zinc-300
                                               px-3 py-1.5 text-xs font-medium
                                               dark:border-zinc-700"
                                    >
                                        Detail
                                    </a>


                                    @if (
                                        ! $document->isRevoked()
                                        &&
                                        $document->hasArchivedPdf()
                                    )
                                        @can('reports.export')
                                            <a
                                                href="{{ route(
                                                    'reports.published-documents.download',
                                                    [
                                                        'code' =>
                                                            $document->code,
                                                    ]
                                                ) }}"
                                                class="rounded-lg bg-zinc-900
                                                       px-3 py-1.5 text-xs font-medium
                                                       text-white dark:bg-white
                                                       dark:text-zinc-900"
                                            >
                                                Download Ulang
                                            </a>
                                        @endcan
                                    @endif


                                    <a
                                        href="{{ route(
                                            'reports.verify',
                                            ['code' => $document->code]
                                        ) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="rounded-lg border border-zinc-300
                                               px-3 py-1.5 text-xs font-medium
                                               dark:border-zinc-700"
                                    >
                                        Verifikasi
                                    </a>


                                    @can('report_verifications.manage')
                                        @if (! $document->isRevoked())
                                            <button
                                                type="button"
                                                wire:click="startRevoke({{ $document->id }})"
                                                class="rounded-lg border border-red-300
                                                       px-3 py-1.5 text-xs font-medium
                                                       text-red-700 dark:border-red-800
                                                       dark:text-red-300"
                                            >
                                                Cabut
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>


                        @if ($revokeId === $document->id)
                            <tr>
                                <td
                                    colspan="8"
                                    class="bg-red-50/70 px-4 py-4 dark:bg-red-950/20"
                                >
                                    <div class="max-w-3xl space-y-3">
                                        <div>
                                            <div
                                                class="font-medium text-red-800
                                                       dark:text-red-300"
                                            >
                                                Cabut Dokumen
                                            </div>

                                            <p
                                                class="mt-1 text-xs leading-5 text-red-700
                                                       dark:text-red-400"
                                            >
                                                QR tidak akan dihapus. Saat dipindai,
                                                status dokumen berubah menjadi Dicabut
                                                sehingga histori tetap dapat diverifikasi.
                                            </p>
                                        </div>

                                        <textarea
                                            wire:model="revocationReason"
                                            rows="3"
                                            class="w-full rounded-lg border border-red-300
                                                   bg-white px-3 py-2 text-sm
                                                   dark:border-red-800 dark:bg-zinc-950"
                                            placeholder="Tuliskan alasan pencabutan dokumen..."
                                        ></textarea>

                                        @error('revocationReason')
                                            <p class="text-sm text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror

                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                wire:click="revoke"
                                                wire:confirm="Cabut dokumen ini? QR lama akan tetap dapat diverifikasi dengan status Dicabut."
                                                wire:loading.attr="disabled"
                                                wire:target="revoke"
                                                class="rounded-lg bg-red-700 px-4 py-2
                                                       text-sm font-medium text-white"
                                            >
                                                Konfirmasi Cabut
                                            </button>

                                            <button
                                                type="button"
                                                wire:click="cancelRevoke"
                                                class="rounded-lg border border-zinc-300
                                                       px-4 py-2 text-sm
                                                       dark:border-zinc-700"
                                            >
                                                Batal
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif

                    @empty
                        <tr>
                            <td
                                colspan="8"
                                class="px-4 py-12 text-center text-zinc-500"
                            >
                                Belum ada dokumen terbit yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($documents->hasPages())
            <div
                class="border-t border-zinc-200 px-4 py-4
                       dark:border-zinc-800"
            >
                {{ $documents->links() }}
            </div>
        @endif
    </section>
</div>
