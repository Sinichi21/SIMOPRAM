<div class="space-y-6">

    {{-- HEADER --}}

    <div
        class="flex flex-col gap-4
               lg:flex-row
               lg:items-start
               lg:justify-between"
    >

        <div>
            <div
                class="text-sm text-zinc-500"
            >
                Input Nilai Kegiatan
            </div>

            <h1
                class="mt-1 text-2xl
                       font-semibold"
            >
                {{ $assessment->title }}
            </h1>

            <p
                class="mt-1 text-sm
                       text-zinc-500"
            >
                {{ $assessment->activity?->title ?? '-' }}
                ·
                Faktor:
                {{ $assessment->factor?->name ?? '-' }}
                ·
                {{ $assessment->mode === 'individual'
                    ? 'Individu'
                    : 'Regu' }}
            </p>
        </div>


        <a
            href="{{ route(
                'activity-assessments.edit',
                $assessment->id
            ) }}"
            wire:navigate
            class="rounded-lg border
                   border-zinc-300
                   px-4 py-2
                   text-sm font-medium
                   dark:border-zinc-700"
        >
            Kelola Form
        </a>

    </div>


    @if (session('status'))

        <div
            class="rounded-lg border
                   border-green-200
                   bg-green-50
                   px-4 py-3
                   text-sm text-green-700
                   dark:border-green-900
                   dark:bg-green-950
                   dark:text-green-300"
        >
            {{ session('status') }}
        </div>

    @endif

    @error('semester')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror

    <div
        class="rounded-xl border
            border-blue-200
            bg-blue-50
            px-4 py-3
            text-sm
            text-blue-700
            dark:border-blue-900
            dark:bg-blue-950/40
            dark:text-blue-300"
    >
        <div class="font-medium">
            Rekap nilai otomatis
        </div>

        <p class="mt-1 leading-6">
            Setiap nilai yang disimpan pada form ini
            akan direkap ke faktor
            <strong>
                {{ $assessment->factor?->name ?? '-' }}
            </strong>.

            Perubahan rekap akan membuat nilai akhir
            siswa perlu dihitung ulang.
        </p>
    </div>

    {{-- PROGRESS --}}

    <div
        class="rounded-xl border
               border-zinc-200
               bg-white p-5
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div
            class="flex items-center
                   justify-between gap-4"
        >
            <div>
                <div
                    class="text-sm text-zinc-500"
                >
                    Progres Penilaian
                </div>

                <div
                    class="mt-1 text-xl
                           font-semibold"
                >
                    {{ $assessedCount }}
                    /
                    {{ $totalTargets }}
                </div>
            </div>


            @php
                $progress =
                    $totalTargets > 0
                        ? min(
                            100,
                            (
                                $assessedCount
                                /
                                $totalTargets
                            )
                            * 100
                        )
                        : 0;
            @endphp

            <div
                class="text-sm font-medium"
            >
                {{ number_format(
                    $progress,
                    0
                ) }}%
            </div>
        </div>


        <div
            class="mt-3 h-2 overflow-hidden
                   rounded-full
                   bg-zinc-100
                   dark:bg-zinc-800"
        >
            <div
                class="h-full rounded-full
                       bg-zinc-900
                       dark:bg-white"
                style="width: {{ $progress }}%"
            ></div>
        </div>

    </div>


    <div
        class="grid gap-6
               lg:grid-cols-[320px_minmax(0,1fr)]"
    >

        {{-- TARGET LIST --}}

        <aside
            class="rounded-xl border
                   border-zinc-200
                   bg-white
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="border-b
                       border-zinc-200
                       p-4
                       dark:border-zinc-800"
            >

                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ $assessment->mode === 'individual'
                        ? 'Cari siswa...'
                        : 'Cari regu...' }}"
                    class="w-full rounded-lg
                           border border-zinc-300
                           bg-white px-3 py-2
                           text-sm
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                >

            </div>


            <div
                class="max-h-[650px]
                       overflow-y-auto"
            >

                @forelse (
                    $targets
                    as $target
                )

                    @php
                        $label =
                            $assessment->mode
                            === 'individual'
                                ? (
                                    $target
                                        ->student
                                        ?->name
                                    ?? 'Siswa tidak tersedia'
                                )
                                : (
                                    $target
                                        ->scoutUnit
                                        ?->name
                                    ?? 'Regu tidak tersedia'
                                );
                    @endphp

                    <button
                        type="button"
                        wire:key="target-{{ $target->id }}"
                        wire:click="selectTarget({{ $target->id }})"
                        class="flex w-full
                               items-start
                               justify-between
                               gap-3
                               border-b
                               border-zinc-100
                               px-4 py-3
                               text-left
                               transition
                               hover:bg-zinc-50
                               dark:border-zinc-800
                               dark:hover:bg-zinc-800
                               {{ $selectedTargetId === $target->id
                                    ? 'bg-zinc-100 dark:bg-zinc-800'
                                    : '' }}"
                    >

                        <div class="min-w-0">

                            <div
                                class="truncate
                                       text-sm
                                       font-medium"
                            >
                                {{ $label }}
                            </div>


                            @if (
                                $assessment->mode
                                === 'individual'
                            )

                                <div
                                    class="mt-1 text-xs
                                           text-zinc-500"
                                >
                                    NIS:
                                    {{ $target->student?->nis ?? '-' }}
                                </div>

                            @else

                                <div
                                    class="mt-1 text-xs
                                           text-zinc-500"
                                >
                                    {{ $target->members->count() }}
                                    anggota
                                </div>

                            @endif

                        </div>


                        @if (
                            $target->assessed_at
                        )

                            <div
                                class="shrink-0
                                       rounded-full
                                       bg-green-100
                                       px-2 py-1
                                       text-xs font-medium
                                       text-green-700
                                       dark:bg-green-950
                                       dark:text-green-300"
                            >
                                {{ number_format(
                                    $target->normalized_score,
                                    2
                                ) }}
                            </div>

                        @else

                            <div
                                class="shrink-0
                                       text-xs
                                       text-zinc-400"
                            >
                                Belum
                            </div>

                        @endif

                    </button>

                @empty

                    <div
                        class="p-6 text-center
                               text-sm
                               text-zinc-500"
                    >
                        Tidak ada target penilaian.
                    </div>

                @endforelse

            </div>

        </aside>


        {{-- SCORE FORM --}}

        <main>

            @if ($selectedTarget)

                <form
                    wire:submit="save"
                    class="space-y-6"
                >

                    <section
                        class="rounded-xl border
                               border-zinc-200
                               bg-white p-6
                               dark:border-zinc-800
                               dark:bg-zinc-900"
                    >

                        <div
                            class="flex flex-col
                                   gap-4
                                   sm:flex-row
                                   sm:items-start
                                   sm:justify-between"
                        >

                            <div>

                                @if (
                                    $assessment->mode
                                    === 'individual'
                                )

                                    <h2
                                        class="text-xl
                                               font-semibold"
                                    >
                                        {{ $selectedTarget
                                            ->student
                                            ?->name
                                            ?? '-' }}
                                    </h2>

                                    <p
                                        class="mt-1 text-sm
                                               text-zinc-500"
                                    >
                                        NIS:
                                        {{ $selectedTarget
                                            ->student
                                            ?->nis
                                            ?? '-' }}
                                    </p>

                                @else

                                    <h2
                                        class="text-xl
                                               font-semibold"
                                    >
                                        {{ $selectedTarget
                                            ->scoutUnit
                                            ?->name
                                            ?? '-' }}
                                    </h2>

                                    <p
                                        class="mt-1 text-sm
                                               text-zinc-500"
                                    >
                                        Penilaian Regu
                                    </p>

                                @endif

                            </div>


                            <div
                                class="rounded-xl
                                       bg-zinc-100
                                       px-4 py-3
                                       text-center
                                       dark:bg-zinc-800"
                            >
                                <div
                                    class="text-xs
                                           text-zinc-500"
                                >
                                    Nilai Akhir Form
                                </div>

                                <div
                                    class="mt-1
                                           text-2xl
                                           font-semibold"
                                >
                                    {{ number_format(
                                        $previewScore,
                                        2
                                    ) }}
                                </div>
                            </div>

                        </div>


                        {{-- ANGGOTA REGU --}}

                        @if (
                            $assessment->mode
                            === 'team'
                        )

                            <div
                                class="mt-5 rounded-lg
                                       border
                                       border-zinc-200
                                       bg-zinc-50
                                       p-4
                                       dark:border-zinc-700
                                       dark:bg-zinc-950"
                            >

                                <div
                                    class="text-sm
                                           font-medium"
                                >
                                    Snapshot Anggota Regu
                                </div>

                                <p class="mt-3 text-sm text-zinc-500">
                                    Tambahkan anggota aktif dari master regu ke penerima nilai kegiatan ini.
                                    Penerima sebelumnya tetap disimpan. Pastikan anggota tambahan memang mengikuti kegiatan.
                                </p>
                                <button
                                    type="button"
                                    wire:click="syncMembers"
                                    wire:confirm="Tambahkan anggota aktif regu yang belum tercantum sebagai penerima nilai kegiatan ini? Pastikan mereka memang mengikuti kegiatan. Nilai yang sudah disimpan tetap dipertahankan."
                                    wire:loading.attr="disabled"
                                    wire:target="syncMembers"
                                    class="mt-3 rounded-lg border border-zinc-300 px-3 py-2 text-sm disabled:opacity-50 dark:border-zinc-700"
                                >
                                    <span wire:loading.remove wire:target="syncMembers">Perbarui Anggota Penerima Nilai</span>
                                    <span wire:loading wire:target="syncMembers">Memperbarui anggota...</span>
                                </button>
                                @error('members')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror


                                <div
                                    class="mt-3
                                           flex flex-wrap
                                           gap-2"
                                >

                                    @forelse (
                                        $selectedTarget
                                            ->members
                                        as $member
                                    )

                                        <span
                                            class="rounded-full
                                                   bg-white
                                                   px-3 py-1
                                                   text-xs
                                                   shadow-sm
                                                   dark:bg-zinc-900"
                                        >
                                            {{ $member
                                                ->student
                                                ?->name
                                                ?? '-' }}
                                        </span>

                                    @empty

                                        <span
                                            class="text-sm
                                                   text-zinc-500"
                                        >
                                            Regu belum memiliki
                                            snapshot anggota.
                                        </span>

                                    @endforelse

                                </div>

                            </div>

                        @endif

                    </section>


                    {{-- KRITERIA --}}

                    <section
                        class="rounded-xl border
                               border-zinc-200
                               bg-white p-6
                               dark:border-zinc-800
                               dark:bg-zinc-900"
                    >

                        <h2
                            class="text-lg font-semibold"
                        >
                            Nilai Kriteria
                        </h2>


                        <div
                            class="mt-5 space-y-5"
                        >

                            @foreach (
                                $assessment->criteria
                                as $criterion
                            )

                                <div
                                    wire:key="criterion-score-{{ $criterion->id }}"
                                    class="rounded-lg border
                                           border-zinc-200
                                           p-4
                                           dark:border-zinc-700"
                                >

                                    <div
                                        class="flex flex-col
                                               gap-4
                                               sm:flex-row
                                               sm:items-center
                                               sm:justify-between"
                                    >

                                        <div>

                                            <div
                                                class="font-medium"
                                            >
                                                {{ $criterion->name }}
                                            </div>

                                            <div
                                                class="mt-1
                                                       text-xs
                                                       text-zinc-500"
                                            >
                                                Maksimum:
                                                {{ number_format(
                                                    $criterion->max_score,
                                                    2
                                                ) }}
                                                · Bobot:
                                                {{ number_format(
                                                    $criterion->weight,
                                                    2
                                                ) }}%
                                            </div>


                                            @if (
                                                $criterion->description
                                            )

                                                <div
                                                    class="mt-2
                                                           text-sm
                                                           text-zinc-500"
                                                >
                                                    {{ $criterion->description }}
                                                </div>

                                            @endif

                                        </div>


                                        <div
                                            class="w-full
                                                   sm:w-40"
                                        >

                                            <input
                                                type="number"
                                                wire:model.live.debounce.300ms="scores.{{ $criterion->id }}"
                                                min="0"
                                                max="{{ $criterion->max_score }}"
                                                step="0.01"
                                                class="w-full rounded-lg
                                                       border
                                                       border-zinc-300
                                                       bg-white
                                                       px-3 py-2
                                                       text-right
                                                       dark:border-zinc-700
                                                       dark:bg-zinc-950"
                                            >

                                            @error(
                                                'scores.'
                                                . $criterion->id
                                            )
                                                <div
                                                    class="mt-1
                                                           text-xs
                                                           text-red-600"
                                                >
                                                    {{ $message }}
                                                </div>
                                            @enderror

                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </section>


                    {{-- CATATAN --}}

                    <section
                        class="rounded-xl border
                               border-zinc-200
                               bg-white p-6
                               dark:border-zinc-800
                               dark:bg-zinc-900"
                    >

                        <label
                            class="block
                                   text-sm
                                   font-medium"
                        >
                            Catatan Penilaian
                        </label>

                        <textarea
                            wire:model="notes"
                            rows="4"
                            class="mt-2 w-full
                                   rounded-lg border
                                   border-zinc-300
                                   bg-white px-3 py-2
                                   dark:border-zinc-700
                                   dark:bg-zinc-950"
                        ></textarea>

                    </section>


                    @can(
                        'activity_assessments.score'
                    )

                        <div
                            class="flex justify-end"
                        >
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="save"
                                class="rounded-lg
                                       bg-zinc-900
                                       px-6 py-2.5
                                       text-sm font-medium
                                       text-white
                                       disabled:opacity-50
                                       dark:bg-white
                                       dark:text-zinc-900"
                            >
                                <span
                                    wire:loading.remove
                                    wire:target="save"
                                >
                                    Simpan Nilai
                                </span>

                                <span
                                    wire:loading
                                    wire:target="save"
                                >
                                    Menyimpan...
                                </span>
                            </button>
                        </div>

                    @endcan

                </form>

            @else

                <div
                    class="rounded-xl border
                           border-zinc-200
                           bg-white p-10
                           text-center
                           text-zinc-500
                           dark:border-zinc-800
                           dark:bg-zinc-900"
                >
                    Pilih siswa atau regu
                    untuk mulai melakukan penilaian.
                </div>

            @endif

        </main>

    </div>

</div>
