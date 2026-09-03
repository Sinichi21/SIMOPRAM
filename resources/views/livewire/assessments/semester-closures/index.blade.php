<div class="space-y-6">

    <div
        class="flex flex-col gap-4
               lg:flex-row
               lg:items-start
               lg:justify-between"
    >
        <div>
            <h1 class="text-2xl font-semibold">
                Kunci Semester
            </h1>

            <p
                class="mt-1 max-w-3xl
                       text-sm leading-6
                       text-zinc-500"
            >
                Simpan nilai akhir semester sebagai
                snapshot resmi yang tidak berubah
                meskipun data operasional kemudian
                mengalami perubahan.
            </p>
        </div>
    </div>


    @if (session('status'))

        <div
            class="rounded-xl border
                   border-green-200
                   bg-green-50
                   px-4 py-3
                   text-sm text-green-700
                   dark:border-green-900
                   dark:bg-green-950/40
                   dark:text-green-300"
        >
            {{ session('status') }}
        </div>

    @endif


    @error('semester')

        <div
            class="rounded-xl border
                   border-red-200
                   bg-red-50
                   px-4 py-3
                   text-sm text-red-700
                   dark:border-red-900
                   dark:bg-red-950/40
                   dark:text-red-300"
        >
            {{ $message }}
        </div>

    @enderror


    {{-- FILTER --}}

    <section
        class="rounded-xl border
               border-zinc-200
               bg-white p-5
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div
            class="grid gap-4
                   md:grid-cols-2"
        >

            <div>
                <label
                    class="mb-1 block
                           text-sm font-medium"
                >
                    Tahun Ajaran
                </label>

                <select
                    wire:model.live="academicYearId"
                    class="w-full rounded-lg
                           border border-zinc-300
                           bg-white px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                >
                    <option value="">
                        -- Pilih Tahun Ajaran --
                    </option>

                    @foreach (
                        $academicYears
                        as $year
                    )

                        <option
                            value="{{ $year->id }}"
                        >
                            {{ $year->name }}
                        </option>

                    @endforeach
                </select>
            </div>


            <div>
                <label
                    class="mb-1 block
                           text-sm font-medium"
                >
                    Semester
                </label>

                <select
                    wire:model.live="semesterId"
                    class="w-full rounded-lg
                           border border-zinc-300
                           bg-white px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                >
                    <option value="">
                        -- Pilih Semester --
                    </option>

                    @foreach (
                        $semesters
                        as $semester
                    )

                        <option
                            value="{{ $semester->id }}"
                        >
                            {{ $semester->name }}
                        </option>

                    @endforeach
                </select>
            </div>

        </div>

    </section>


    @if (
        $academicYearId
        &&
        $semesterId
    )

        {{-- STATUS --}}

        <div
            class="grid gap-4
                   md:grid-cols-3"
        >

            <div
                class="rounded-xl border
                       border-zinc-200
                       bg-white p-5
                       dark:border-zinc-800
                       dark:bg-zinc-900"
            >
                <div class="text-xs text-zinc-500">
                    Status Semester
                </div>

                <div
                    class="mt-2 text-xl
                           font-semibold"
                >
                    {{ $isLocked
                        ? 'Terkunci'
                        : 'Terbuka' }}
                </div>
            </div>


            <div
                class="rounded-xl border
                       border-zinc-200
                       bg-white p-5
                       dark:border-zinc-800
                       dark:bg-zinc-900"
            >
                <div class="text-xs text-zinc-500">
                    Nilai Kehadiran Stale
                </div>

                <div
                    class="mt-2 text-xl
                           font-semibold"
                >
                    {{ number_format(
                        $syncStatus[
                            'attendance'
                        ][
                            'stale_count'
                        ]
                        ?? 0
                    ) }}
                </div>
            </div>


            <div
                class="rounded-xl border
                       border-zinc-200
                       bg-white p-5
                       dark:border-zinc-800
                       dark:bg-zinc-900"
            >
                <div class="text-xs text-zinc-500">
                    Nilai Akhir Stale
                </div>

                <div
                    class="mt-2 text-xl
                           font-semibold"
                >
                    {{ number_format(
                        $syncStatus[
                            'final'
                        ][
                            'stale_count'
                        ]
                        ?? 0
                    ) }}
                </div>
            </div>

        </div>


        {{-- LOCK CONTROL --}}

        <section
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-6
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            @if (! $config)

                <div class="text-sm text-zinc-500">
                    Konfigurasi penilaian aktif
                    tidak ditemukan.
                </div>

            @elseif ($isLocked)

                <div
                    class="rounded-lg border
                           border-green-200
                           bg-green-50
                           p-4
                           text-sm text-green-700
                           dark:border-green-900
                           dark:bg-green-950/40
                           dark:text-green-300"
                >
                    Semester telah dikunci pada
                    <strong>
                        versi
                        {{ $currentClosure->version }}
                    </strong>.

                    Terdapat
                    <strong>
                        {{ number_format(
                            $currentClosure
                                ->snapshot_count
                        ) }}
                    </strong>
                    snapshot nilai resmi.
                </div>


                @can('semester_closures.manage')

                    @if (
                        $reopenClosureId
                        === $currentClosure->id
                    )

                        <div
                            class="mt-5 rounded-lg
                                   border border-amber-200
                                   p-4
                                   dark:border-amber-900"
                        >

                            <label
                                class="block
                                       text-sm font-medium"
                            >
                                Alasan Membuka Semester
                            </label>

                            <textarea
                                wire:model="reopenReason"
                                rows="3"
                                class="mt-2 w-full
                                       rounded-lg border
                                       border-zinc-300
                                       bg-white px-3 py-2
                                       dark:border-zinc-700
                                       dark:bg-zinc-950"
                                placeholder="Contoh: Koreksi nilai siswa karena terdapat kesalahan input."
                            ></textarea>

                            @error('reopenReason')
                                <p
                                    class="mt-1
                                           text-sm
                                           text-red-600"
                                >
                                    {{ $message }}
                                </p>
                            @enderror


                            <div
                                class="mt-4 flex
                                       flex-wrap gap-2"
                            >
                                <button
                                    type="button"
                                    wire:click="reopenSemester"
                                    wire:confirm="Buka kembali semester ini? Snapshot versi lama tetap disimpan."
                                    class="rounded-lg
                                           bg-amber-700
                                           px-4 py-2
                                           text-sm font-medium
                                           text-white"
                                >
                                    Konfirmasi Buka Semester
                                </button>

                                <button
                                    type="button"
                                    wire:click="cancelReopen"
                                    class="rounded-lg border
                                           border-zinc-300
                                           px-4 py-2
                                           text-sm
                                           dark:border-zinc-700"
                                >
                                    Batal
                                </button>
                            </div>

                        </div>

                    @else

                        <div class="mt-5">

                            <button
                                type="button"
                                wire:click="startReopen({{ $currentClosure->id }})"
                                class="rounded-lg border
                                       border-amber-300
                                       px-4 py-2
                                       text-sm font-medium
                                       text-amber-700
                                       dark:border-amber-800
                                       dark:text-amber-300"
                            >
                                Buka Kembali Semester
                            </button>

                        </div>

                    @endif

                @endcan

            @else

                @if (! $canLock)

                    <div
                        class="rounded-lg border
                               border-amber-200
                               bg-amber-50
                               p-4
                               text-sm text-amber-800
                               dark:border-amber-900
                               dark:bg-amber-950/40
                               dark:text-amber-300"
                    >
                        Semester belum dapat dikunci.

                        Pastikan seluruh nilai kehadiran
                        dan nilai akhir sudah sinkron.
                    </div>


                    @can('assessment_sync.view')

                        <div class="mt-4">

                            <a
                                href="{{ route(
                                    'assessment-sync.index'
                                ) }}"
                                wire:navigate
                                class="inline-flex
                                       rounded-lg
                                       bg-amber-700
                                       px-4 py-2
                                       text-sm font-medium
                                       text-white"
                            >
                                Buka Sinkronisasi Penilaian
                            </a>

                        </div>

                    @endcan

                @else

                    <div
                        class="rounded-lg border
                               border-green-200
                               bg-green-50
                               p-4
                               text-sm text-green-700
                               dark:border-green-900
                               dark:bg-green-950/40
                               dark:text-green-300"
                    >
                        Seluruh data penilaian sudah
                        sinkron dan semester siap dikunci.
                    </div>


                    @can('semester_closures.manage')

                        <div class="mt-5">

                            <button
                                type="button"
                                wire:click="lockSemester"
                                wire:confirm="Kunci semester dan buat snapshot nilai resmi? Setelah terkunci, data penilaian periode ini tidak boleh diubah tanpa membuka semester."
                                wire:loading.attr="disabled"
                                wire:target="lockSemester"
                                class="rounded-lg
                                       bg-zinc-900
                                       px-5 py-2.5
                                       text-sm font-medium
                                       text-white
                                       dark:bg-white
                                       dark:text-zinc-900"
                            >
                                <span
                                    wire:loading.remove
                                    wire:target="lockSemester"
                                >
                                    Kunci Semester
                                </span>

                                <span
                                    wire:loading
                                    wire:target="lockSemester"
                                >
                                    Membuat Snapshot...
                                </span>
                            </button>

                        </div>

                    @endcan

                @endif

            @endif

        </section>


        {{-- HISTORY --}}

        <section
            class="overflow-hidden
                   rounded-xl border
                   border-zinc-200
                   bg-white
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="border-b border-zinc-200
                       p-5
                       dark:border-zinc-800"
            >
                <h2 class="font-semibold">
                    Riwayat Penguncian Semester
                </h2>

                <p
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Setiap penguncian menghasilkan
                    versi snapshot baru.
                </p>
            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-sm">

                    <thead
                        class="bg-zinc-50
                               dark:bg-zinc-950"
                    >
                        <tr>
                            <th class="px-4 py-3 text-left">
                                Versi
                            </th>

                            <th class="px-4 py-3 text-left">
                                Status
                            </th>

                            <th class="px-4 py-3 text-left">
                                Dikunci
                            </th>

                            <th class="px-4 py-3 text-left">
                                Oleh
                            </th>

                            <th class="px-4 py-3 text-right">
                                Snapshot
                            </th>

                            <th class="px-4 py-3 text-left">
                                Checksum
                            </th>
                        </tr>
                    </thead>


                    <tbody
                        class="divide-y
                               divide-zinc-200
                               dark:divide-zinc-800"
                    >

                        @forelse (
                            $history
                            as $closure
                        )

                            <tr>
                                <td
                                    class="px-4 py-4
                                           font-medium"
                                >
                                    v{{ $closure->version }}
                                </td>

                                <td class="px-4 py-4">

                                    @if ($closure->isLocked())

                                        <span
                                            class="rounded-full
                                                   bg-green-100
                                                   px-2.5 py-1
                                                   text-xs font-medium
                                                   text-green-700
                                                   dark:bg-green-950
                                                   dark:text-green-300"
                                        >
                                            Terkunci
                                        </span>

                                    @else

                                        <span
                                            class="rounded-full
                                                   bg-amber-100
                                                   px-2.5 py-1
                                                   text-xs font-medium
                                                   text-amber-700
                                                   dark:bg-amber-950
                                                   dark:text-amber-300"
                                        >
                                            Dibuka Kembali
                                        </span>

                                    @endif

                                </td>

                                <td
                                    class="whitespace-nowrap
                                           px-4 py-4"
                                >
                                    {{ $closure
                                        ->locked_at
                                        ?->format(
                                            'd/m/Y H:i'
                                        ) }}
                                </td>

                                <td class="px-4 py-4">
                                    {{ $closure
                                        ->locker
                                        ?->name
                                        ?? 'Sistem' }}
                                </td>

                                <td
                                    class="px-4 py-4
                                           text-right"
                                >
                                    {{ number_format(
                                        $closure
                                            ->snapshot_count
                                    ) }}
                                </td>

                                <td class="px-4 py-4">
                                    <code
                                        class="text-xs
                                               text-zinc-500"
                                        title="{{ $closure->snapshot_checksum }}"
                                    >
                                        {{ $closure->snapshot_checksum
                                            ? substr(
                                                $closure->snapshot_checksum,
                                                0,
                                                12
                                            )
                                                . '...'
                                            : '-' }}
                                    </code>
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="6"
                                    class="px-4 py-10
                                           text-center
                                           text-zinc-500"
                                >
                                    Semester ini belum
                                    pernah dikunci.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>

    @endif

</div>