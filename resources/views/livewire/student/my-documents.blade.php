<div class="mx-auto max-w-7xl space-y-6">

    {{-- =====================================================
    HEADER
    ====================================================== --}}

    <div
        class="flex flex-col
               justify-between gap-4
               lg:flex-row lg:items-end"
    >

        <div>

            <h1
                class="text-2xl font-bold
                       tracking-tight
                       text-zinc-900
                       dark:text-white"
            >
                Dokumen Saya
            </h1>

            <p
                class="mt-1 text-sm
                       text-zinc-500
                       dark:text-zinc-400"
            >
                Dokumen resmi SIMPRAM
                yang diterbitkan untuk Anda.
            </p>

        </div>

    </div>


    @if (! $student)

        {{-- =================================================
        STUDENT BELUM TERHUBUNG
        ================================================== --}}

        <div
            class="rounded-2xl
                   border border-amber-200
                   bg-amber-50 p-6
                   dark:border-amber-900/50
                   dark:bg-amber-950/30"
        >

            <div class="flex gap-4">

                <flux:icon.exclamation-triangle
                    class="size-6 shrink-0
                           text-amber-600"
                />

                <div>

                    <div
                        class="font-semibold
                               text-zinc-900
                               dark:text-white"
                    >
                        Data siswa belum terhubung
                    </div>

                    <p
                        class="mt-1 text-sm
                               text-zinc-600
                               dark:text-zinc-400"
                    >
                        Hubungi administrator sekolah
                        untuk menghubungkan akun
                        dengan data siswa.
                    </p>

                </div>

            </div>

        </div>

    @else

        {{-- =================================================
        STATISTIK
        ================================================== --}}

        <section
            class="grid gap-3
                   sm:grid-cols-3"
        >

            <div
                class="rounded-2xl border
                       border-zinc-200
                       bg-white p-5
                       dark:border-zinc-800
                       dark:bg-zinc-900"
            >
                <div
                    class="flex size-10
                           items-center
                           justify-center
                           rounded-xl
                           bg-blue-50
                           text-blue-600
                           dark:bg-blue-950/50
                           dark:text-blue-400"
                >
                    <flux:icon.document-text
                        class="size-5"
                    />
                </div>

                <div
                    class="mt-4 text-2xl
                           font-bold
                           text-zinc-900
                           dark:text-white"
                >
                    {{ $statistics['total'] }}
                </div>

                <div
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Total Dokumen
                </div>
            </div>


            <div
                class="rounded-2xl border
                       border-zinc-200
                       bg-white p-5
                       dark:border-zinc-800
                       dark:bg-zinc-900"
            >
                <div
                    class="flex size-10
                           items-center
                           justify-center
                           rounded-xl
                           bg-emerald-50
                           text-emerald-600
                           dark:bg-emerald-950/50
                           dark:text-emerald-400"
                >
                    <flux:icon.check-badge
                        class="size-5"
                    />
                </div>

                <div
                    class="mt-4 text-2xl
                           font-bold
                           text-zinc-900
                           dark:text-white"
                >
                    {{ $statistics['valid'] }}
                </div>

                <div
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Aktif
                </div>
            </div>


            <div
                class="rounded-2xl border
                       border-zinc-200
                       bg-white p-5
                       dark:border-zinc-800
                       dark:bg-zinc-900"
            >
                <div
                    class="flex size-10
                           items-center
                           justify-center
                           rounded-xl
                           bg-red-50
                           text-red-600
                           dark:bg-red-950/50
                           dark:text-red-400"
                >
                    <flux:icon.x-circle
                        class="size-5"
                    />
                </div>

                <div
                    class="mt-4 text-2xl
                           font-bold
                           text-zinc-900
                           dark:text-white"
                >
                    {{ $statistics['revoked'] }}
                </div>

                <div
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Dicabut
                </div>
            </div>

        </section>


        {{-- =================================================
        FILTER
        ================================================== --}}

        <section
            class="rounded-2xl border
                   border-zinc-200
                   bg-white p-4
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="grid gap-3
                       md:grid-cols-[1fr_240px]"
            >

                <div
                    class="relative"
                >

                    <flux:icon.magnifying-glass
                        class="absolute
                               left-3 top-1/2
                               size-4
                               -translate-y-1/2
                               text-zinc-400"
                    />

                    <input
                        type="search"
                        wire:model.live.debounce.400ms="
                            search
                        "
                        placeholder="
                            Cari dokumen, nomor,
                            atau kode...
                        "
                        class="w-full rounded-lg
                               border border-zinc-300
                               bg-white py-2.5
                               pl-9 pr-3
                               text-sm
                               dark:border-zinc-700
                               dark:bg-zinc-950"
                    >

                </div>


                <select
                    wire:model.live="
                        documentType
                    "
                    class="rounded-lg
                           border border-zinc-300
                           bg-white px-3
                           py-2.5 text-sm
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                >

                    <option value="">
                        Semua Jenis
                    </option>

                    @foreach (
                        $documentTypes
                        as $type
                    )

                        <option
                            value="{{ $type }}"
                        >
                            {{
                                match ($type) {
                                    'grades' =>
                                        'Rekap Nilai',

                                    'attendance' =>
                                        'Rekap Absensi',

                                    'lpj' =>
                                        'LPJ Kegiatan',

                                    'letter' =>
                                        'Surat',

                                    'certificate' =>
                                        'Sertifikat',

                                    default =>
                                        str($type)
                                            ->replace(
                                                '_',
                                                ' '
                                            )
                                            ->title(),
                                }
                            }}
                        </option>

                    @endforeach

                </select>

            </div>

        </section>


        {{-- =================================================
        DOKUMEN
        ================================================== --}}

        <section
            class="grid gap-4
                   md:grid-cols-2
                   xl:grid-cols-3"
        >

            @forelse (
                $documents
                as $document
            )

                @php
                    $status =
                        $document
                            ->publicStatus();

                    $statusData =
                        match ($status) {
                            'valid' => [
                                'Aktif',
                                'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                            ],

                            'revoked' => [
                                'Dicabut',
                                'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300',
                            ],

                            'superseded' => [
                                'Digantikan',
                                'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
                            ],

                            default => [
                                ucfirst($status),
                                'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                            ],
                        };

                    $typeLabel =
                        match (
                            $document
                                ->document_type
                        ) {
                            'grades' =>
                                'Rekap Nilai',

                            'attendance' =>
                                'Rekap Absensi',

                            'lpj' =>
                                'LPJ Kegiatan',

                            'letter' =>
                                'Surat',

                            'certificate' =>
                                'Sertifikat',

                            default =>
                                str(
                                    $document
                                        ->document_type
                                )
                                    ->replace(
                                        '_',
                                        ' '
                                    )
                                    ->title(),
                        };
                @endphp


                <article
                    class="flex flex-col
                           rounded-2xl
                           border border-zinc-200
                           bg-white p-5
                           dark:border-zinc-800
                           dark:bg-zinc-900"
                >

                    <div
                        class="flex items-start
                               justify-between
                               gap-4"
                    >

                        <div
                            class="flex size-11
                                   shrink-0
                                   items-center
                                   justify-center
                                   rounded-xl
                                   bg-blue-50
                                   text-blue-600
                                   dark:bg-blue-950/50
                                   dark:text-blue-400"
                        >
                            <flux:icon.document-text
                                class="size-5"
                            />
                        </div>


                        <span
                            class="rounded-full
                                   px-2.5 py-1
                                   text-xs
                                   font-medium
                                   {{ $statusData[1] }}"
                        >
                            {{ $statusData[0] }}
                        </span>

                    </div>


                    <div class="mt-4">

                        <div
                            class="text-xs
                                   font-medium
                                   uppercase
                                   tracking-wide
                                   text-zinc-400"
                        >
                            {{ $typeLabel }}
                        </div>

                        <h2
                            class="mt-1
                                   line-clamp-2
                                   text-base
                                   font-semibold
                                   text-zinc-900
                                   dark:text-white"
                        >
                            {{
                                $document->title
                                ?: $typeLabel
                            }}
                        </h2>


                        @if (
                            filled(
                                $document
                                    ->document_number
                            )
                        )

                            <div
                                class="mt-2 text-sm
                                       text-zinc-500"
                            >
                                No.
                                {{
                                    $document
                                        ->document_number
                                }}
                            </div>

                        @endif

                    </div>


                    <div
                        class="mt-4 space-y-2
                               text-sm
                               text-zinc-500"
                    >

                        <div
                            class="flex
                                   items-center
                                   gap-2"
                        >

                            <flux:icon.calendar
                                class="size-4"
                            />

                            {{
                                $document
                                    ->issued_at
                                    ?->locale('id')
                                    ->translatedFormat(
                                        'd F Y'
                                    )
                                ?? '-'
                            }}

                        </div>


                        @if (
                            $document
                                ->closure
                                ?->semester
                        )

                            <div
                                class="flex
                                       items-center
                                       gap-2"
                            >

                                <flux:icon.academic-cap
                                    class="size-4"
                                />

                                {{
                                    $document
                                        ->closure
                                        ?->academicYear
                                        ?->name
                                    ?? '-'
                                }}

                                ·

                                {{
                                    $document
                                        ->closure
                                        ?->semester
                                        ?->name
                                }}

                            </div>

                        @endif

                    </div>


                    <div class="mt-auto pt-5">

                        <div
                            class="flex gap-2"
                        >

                            {{-- Verifikasi publik --}}
                            <a
                                href="{{
                                    route(
                                        'reports.verify',
                                        [
                                            'code' =>
                                                $document
                                                    ->code,
                                        ]
                                    )
                                }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex
                                       flex-1
                                       items-center
                                       justify-center
                                       gap-2
                                       rounded-lg
                                       border
                                       border-zinc-200
                                       px-3 py-2
                                       text-sm
                                       font-medium
                                       transition
                                       hover:bg-zinc-50
                                       dark:border-zinc-700
                                       dark:hover:bg-zinc-800"
                            >
                                <flux:icon.shield-check
                                    class="size-4"
                                />

                                Verifikasi
                            </a>


                            @if (
                                $status
                                === 'valid'
                            )

                                <a
                                    href="{{
                                        route(
                                            'student.documents.download',
                                            [
                                                'code' =>
                                                    $document
                                                        ->code,
                                            ]
                                        )
                                    }}"
                                    class="inline-flex
                                           flex-1
                                           items-center
                                           justify-center
                                           gap-2
                                           rounded-lg
                                           bg-emerald-600
                                           px-3 py-2
                                           text-sm
                                           font-medium
                                           text-white
                                           transition
                                           hover:bg-emerald-700"
                                >
                                    <flux:icon.arrow-down-tray
                                        class="size-4"
                                    />

                                    Unduh
                                </a>

                            @endif

                        </div>

                    </div>

                </article>

            @empty

                <div
                    class="col-span-full
                           rounded-2xl
                           border border-zinc-200
                           bg-white
                           px-6 py-14
                           text-center
                           dark:border-zinc-800
                           dark:bg-zinc-900"
                >

                    <div
                        class="mx-auto flex
                               size-14
                               items-center
                               justify-center
                               rounded-full
                               bg-zinc-100
                               text-zinc-400
                               dark:bg-zinc-800"
                    >
                        <flux:icon.document-text
                            class="size-6"
                        />
                    </div>

                    <h2
                        class="mt-4
                               font-semibold
                               text-zinc-900
                               dark:text-white"
                    >
                        Belum ada dokumen
                    </h2>

                    <p
                        class="mx-auto mt-1
                               max-w-md
                               text-sm
                               leading-6
                               text-zinc-500"
                    >
                        Dokumen resmi yang
                        diterbitkan khusus untuk Anda
                        akan muncul di halaman ini.
                    </p>

                </div>

            @endforelse

        </section>


        @if (
            $documents
            &&
            $documents->hasPages()
        )

            <div>
                {{
                    $documents->links()
                }}
            </div>

        @endif

    @endif

</div>