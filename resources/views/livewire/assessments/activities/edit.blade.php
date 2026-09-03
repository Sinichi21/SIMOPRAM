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
                Penilaian Kegiatan
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
                {{ $assessment->mode === 'individual'
                    ? 'Individu'
                    : 'Regu' }}
            </p>
        </div>


        <div
            class="flex flex-wrap gap-2"
        >
            <a
                href="{{ route(
                    'activity-assessments.index'
                ) }}"
                wire:navigate
                class="rounded-lg border
                       border-zinc-300
                       px-4 py-2
                       text-sm font-medium
                       dark:border-zinc-700"
            >
                Kembali
            </a>


            @if (
                $assessment->status
                === 'published'
            )

                <a
                    href="{{ route(
                        'activity-assessments.score',
                        $assessment->id
                    ) }}"
                    wire:navigate
                    class="rounded-lg
                           bg-zinc-900
                           px-4 py-2
                           text-sm font-medium
                           text-white
                           dark:bg-white
                           dark:text-zinc-900"
                >
                    Input Nilai
                </a>

            @endif
        </div>
    </div>


    {{-- FLASH --}}

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


    @error('form')
        <div
            class="rounded-lg border
                   border-red-200
                   bg-red-50
                   px-4 py-3
                   text-sm text-red-700"
        >
            {{ $message }}
        </div>
    @enderror


    @error('criteria')
        <div
            class="rounded-lg border
                   border-red-200
                   bg-red-50
                   px-4 py-3
                   text-sm text-red-700"
        >
            {{ $message }}
        </div>
    @enderror


    {{-- STATUS --}}

    <div
        class="grid gap-4
               sm:grid-cols-2
               xl:grid-cols-4"
    >

        <div
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-4
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >
            <div
                class="text-xs text-zinc-500"
            >
                Status
            </div>

            <div
                class="mt-1 font-semibold"
            >
                {{ ucfirst(
                    $assessment->status
                ) }}
            </div>
        </div>


        <div
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-4
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >
            <div
                class="text-xs text-zinc-500"
            >
                Total Bobot
            </div>

            <div
                class="mt-1 text-xl
                       font-semibold"
            >
                {{ number_format(
                    $totalWeight,
                    2
                ) }}%
            </div>
        </div>


        <div
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-4
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >
            <div
                class="text-xs text-zinc-500"
            >
                Target
            </div>

            <div
                class="mt-1 text-xl
                       font-semibold"
            >
                {{ number_format(
                    $targetCount
                ) }}
            </div>
        </div>


        <div
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-4
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >
            <div
                class="text-xs text-zinc-500"
            >
                Sudah Dinilai
            </div>

            <div
                class="mt-1 text-xl
                       font-semibold"
            >
                {{ number_format(
                    $assessedCount
                ) }}
            </div>
        </div>

    </div>


    {{-- IDENTITAS FORM --}}

    <form
        wire:submit="saveForm"
        class="rounded-xl border
               border-zinc-200
               bg-white p-6
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <h2
            class="text-lg font-semibold"
        >
            Informasi Form
        </h2>


        <div
            class="mt-5 grid gap-4
                   md:grid-cols-2"
        >

            <div>
                <label
                    class="mb-1 block
                           text-sm font-medium"
                >
                    Nama Form
                </label>

                <input
                    type="text"
                    wire:model="title"
                    @disabled(
                        $assessment->status
                        !== 'draft'
                    )
                    class="w-full rounded-lg
                           border border-zinc-300
                           bg-white px-3 py-2
                           disabled:opacity-60
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                >

                @error('title')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>


            <div>
                <label
                    class="mb-1 block
                           text-sm font-medium"
                >
                    Faktor Tujuan
                </label>

                <select
                    wire:model="assessmentFactorId"
                    @disabled(
                        $assessment->status
                        !== 'draft'
                    )
                    class="w-full rounded-lg
                           border border-zinc-300
                           bg-white px-3 py-2
                           disabled:opacity-60
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                >

                    @foreach (
                        $factors
                        as $factor
                    )

                        <option
                            value="{{ $factor->id }}"
                        >
                            {{ $factor->name }}
                        </option>

                    @endforeach

                </select>

                @error('assessmentFactorId')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>


            <div>
                <label
                    class="mb-1 block
                           text-sm font-medium"
                >
                    Mode
                </label>

                <select
                    wire:model="mode"
                    @disabled(
                        $assessment->status
                        !== 'draft'
                    )
                    class="w-full rounded-lg
                           border border-zinc-300
                           bg-white px-3 py-2
                           disabled:opacity-60
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                >
                    <option value="individual">
                        Individu
                    </option>

                    <option value="team">
                        Regu
                    </option>
                </select>

                @error('mode')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

        </div>


        <div class="mt-4">

            <label
                class="mb-1 block
                       text-sm font-medium"
            >
                Deskripsi
            </label>

            <textarea
                wire:model="description"
                rows="3"
                @disabled(
                    $assessment->status
                    !== 'draft'
                )
                class="w-full rounded-lg
                       border border-zinc-300
                       bg-white px-3 py-2
                       disabled:opacity-60
                       dark:border-zinc-700
                       dark:bg-zinc-950"
            ></textarea>

        </div>


        @if (
            $assessment->status
            === 'draft'
        )

            @can(
                'activity_assessments.update'
            )

                <div
                    class="mt-5 flex
                           justify-end"
                >
                    <button
                        type="submit"
                        class="rounded-lg
                               bg-zinc-900
                               px-5 py-2.5
                               text-sm font-medium
                               text-white
                               dark:bg-white
                               dark:text-zinc-900"
                    >
                        Simpan Informasi
                    </button>
                </div>

            @endcan

        @endif

    </form>


    {{-- KRITERIA --}}

    <section
        class="rounded-xl border
               border-zinc-200
               bg-white p-6
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div
            class="flex flex-col gap-3
                   sm:flex-row
                   sm:items-center
                   sm:justify-between"
        >
            <div>
                <h2
                    class="text-lg font-semibold"
                >
                    Kriteria Penilaian
                </h2>

                <p
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Total bobot kriteria wajib
                    tepat 100%.
                </p>
            </div>


            <div
                class="rounded-full
                       px-3 py-1
                       text-sm font-medium
                       {{ abs($totalWeight - 100) <= 0.01
                            ? 'bg-green-100 text-green-700'
                            : 'bg-amber-100 text-amber-700' }}"
            >
                {{ number_format(
                    $totalWeight,
                    2
                ) }} / 100%
            </div>
        </div>


        @if (
            $assessment->status
            === 'draft'
        )

            @can(
                'activity_assessments.update'
            )

                <form
                    wire:submit="saveCriterion"
                    class="mt-6 rounded-lg
                           border border-zinc-200
                           bg-zinc-50 p-4
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                >

                    <div
                        class="grid gap-4
                               md:grid-cols-2
                               xl:grid-cols-4"
                    >

                        <div>
                            <label
                                class="mb-1 block
                                       text-sm font-medium"
                            >
                                Kriteria
                            </label>

                            <input
                                type="text"
                                wire:model="criterionName"
                                placeholder="Ketepatan simpul"
                                class="w-full rounded-lg
                                       border border-zinc-300
                                       bg-white px-3 py-2
                                       dark:border-zinc-700
                                       dark:bg-zinc-900"
                            >

                            @error('criterionName')
                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>


                        <div>
                            <label
                                class="mb-1 block
                                       text-sm font-medium"
                            >
                                Nilai Maksimum
                            </label>

                            <input
                                type="number"
                                wire:model="criterionMaxScore"
                                min="0.01"
                                step="0.01"
                                class="w-full rounded-lg
                                       border border-zinc-300
                                       bg-white px-3 py-2
                                       dark:border-zinc-700
                                       dark:bg-zinc-900"
                            >
                        </div>


                        <div>
                            <label
                                class="mb-1 block
                                       text-sm font-medium"
                            >
                                Bobot
                            </label>

                            <input
                                type="number"
                                wire:model="criterionWeight"
                                min="0.01"
                                max="100"
                                step="0.01"
                                class="w-full rounded-lg
                                       border border-zinc-300
                                       bg-white px-3 py-2
                                       dark:border-zinc-700
                                       dark:bg-zinc-900"
                            >
                        </div>


                        <div
                            class="flex items-end
                                   gap-2"
                        >
                            <button
                                type="submit"
                                class="rounded-lg
                                       bg-zinc-900
                                       px-4 py-2
                                       text-sm font-medium
                                       text-white
                                       dark:bg-white
                                       dark:text-zinc-900"
                            >
                                {{ $editingCriterionId
                                    ? 'Update'
                                    : 'Tambah' }}
                            </button>


                            @if (
                                $editingCriterionId
                            )

                                <button
                                    type="button"
                                    wire:click="cancelCriterionEdit"
                                    class="rounded-lg
                                           border
                                           border-zinc-300
                                           px-4 py-2
                                           text-sm
                                           dark:border-zinc-700"
                                >
                                    Batal
                                </button>

                            @endif
                        </div>

                    </div>


                    <div class="mt-4">
                        <label
                            class="mb-1 block
                                   text-sm font-medium"
                        >
                            Deskripsi Kriteria
                        </label>

                        <textarea
                            wire:model="criterionDescription"
                            rows="2"
                            class="w-full rounded-lg
                                   border border-zinc-300
                                   bg-white px-3 py-2
                                   dark:border-zinc-700
                                   dark:bg-zinc-900"
                        ></textarea>
                    </div>

                </form>

            @endcan

        @endif


        <div
            class="mt-6 overflow-x-auto"
        >

            <table
                class="w-full text-sm"
            >
                <thead
                    class="bg-zinc-50
                           dark:bg-zinc-950"
                >
                    <tr>
                        <th class="px-4 py-3 text-left">
                            #
                        </th>

                        <th class="px-4 py-3 text-left">
                            Kriteria
                        </th>

                        <th class="px-4 py-3 text-center">
                            Maksimum
                        </th>

                        <th class="px-4 py-3 text-center">
                            Bobot
                        </th>

                        <th class="px-4 py-3 text-right">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody
                    class="divide-y
                           divide-zinc-200
                           dark:divide-zinc-800"
                >

                    @forelse (
                        $assessment->criteria
                        as $index => $criterion
                    )

                        <tr>
                            <td class="px-4 py-3">
                                {{ $index + 1 }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{ $criterion->name }}
                                </div>

                                @if (
                                    $criterion->description
                                )
                                    <div
                                        class="mt-1 text-xs
                                               text-zinc-500"
                                    >
                                        {{ $criterion->description }}
                                    </div>
                                @endif
                            </td>

                            <td
                                class="px-4 py-3
                                       text-center"
                            >
                                {{ number_format(
                                    $criterion->max_score,
                                    2
                                ) }}
                            </td>

                            <td
                                class="px-4 py-3
                                       text-center"
                            >
                                {{ number_format(
                                    $criterion->weight,
                                    2
                                ) }}%
                            </td>

                            <td
                                class="px-4 py-3
                                       text-right"
                            >

                                @if (
                                    $assessment->status
                                    === 'draft'
                                )

                                    <button
                                        type="button"
                                        wire:click="editCriterion({{ $criterion->id }})"
                                        class="mr-3
                                               text-sm font-medium"
                                    >
                                        Edit
                                    </button>


                                    <button
                                        type="button"
                                        wire:click="deleteCriterion({{ $criterion->id }})"
                                        wire:confirm="Hapus kriteria ini?"
                                        class="text-sm
                                               font-medium
                                               text-red-600"
                                    >
                                        Hapus
                                    </button>

                                @else

                                    <span
                                        class="text-zinc-400"
                                    >
                                        Terkunci
                                    </span>

                                @endif

                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="5"
                                class="px-4 py-8
                                       text-center
                                       text-zinc-500"
                            >
                                Belum ada kriteria.
                            </td>
                        </tr>

                    @endforelse

                </tbody>
            </table>

        </div>

    </section>


    {{-- TARGET & PUBLISH --}}

    <section
        class="rounded-xl border
               border-zinc-200
               bg-white p-6
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <h2
            class="text-lg font-semibold"
        >
            Peserta & Publikasi
        </h2>


        <p
            class="mt-1 text-sm
                   text-zinc-500"
        >
            @if (
                $assessment->mode
                === 'individual'
            )
                Sistem akan membuat satu target
                penilaian untuk setiap siswa aktif
                pada tahun ajaran kegiatan.
            @else
                Sistem akan membuat target per regu
                dan menyimpan snapshot anggota regu.
            @endif
        </p>


        <div
            class="mt-5 flex
                   flex-wrap gap-3"
        >

            @if (
                $assessment->status
                === 'draft'
            )

                @can(
                    'activity_assessments.update'
                )

                    <button
                        type="button"
                        wire:click="prepareTargets"
                        wire:loading.attr="disabled"
                        wire:target="prepareTargets"
                        class="rounded-lg
                               border border-zinc-300
                               px-4 py-2
                               text-sm font-medium
                               dark:border-zinc-700"
                    >
                        Siapkan Peserta
                    </button>

                @endcan


                @can(
                    'activity_assessments.publish'
                )

                    <button
                        type="button"
                        wire:click="publish"
                        wire:confirm="Publikasikan form ini dan siapkan peserta penilaian?"
                        wire:loading.attr="disabled"
                        wire:target="publish"
                        @disabled(
                            abs(
                                $totalWeight - 100
                            ) > 0.01
                        )
                        class="rounded-lg
                               bg-green-700
                               px-4 py-2
                               text-sm font-medium
                               text-white
                               disabled:cursor-not-allowed
                               disabled:opacity-50"
                    >
                        Publish
                    </button>

                @endcan

            @else

                @can(
                    'activity_assessments.publish'
                )

                    <button
                        type="button"
                        wire:click="reopen"
                        wire:confirm="Kembalikan form ke Draft?"
                        class="rounded-lg
                               border border-amber-300
                               px-4 py-2
                               text-sm font-medium
                               text-amber-700"
                    >
                        Kembalikan ke Draft
                    </button>

                @endcan

            @endif

        </div>

    </section>

</div>