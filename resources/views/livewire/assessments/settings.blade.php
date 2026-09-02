<div class="space-y-8">

    @if (session('success'))

        <div
            class="rounded-lg border border-green-200
                   bg-green-50 p-4 text-sm text-green-700
                   dark:border-green-900
                   dark:bg-green-950
                   dark:text-green-300"
        >
            {{ session('success') }}
        </div>

    @endif


    <div>
        <h1 class="text-2xl font-semibold">
            Pengaturan Penilaian
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            Atur faktor dan bobot penilaian Pramuka.
        </p>
    </div>


    {{-- =====================================================
    FAKTOR PENILAIAN
    ====================================================== --}}

    <section
        class="rounded-xl border border-zinc-200
               bg-white p-6 shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <h2 class="text-lg font-semibold">
            Faktor Penilaian
        </h2>

        <p class="mt-1 text-sm text-zinc-500">
            Contoh: Kehadiran, Keaktifan,
            Keterampilan, Disiplin, SKU.
        </p>


        @can('assessment_factors.manage')

            <form
                wire:submit="saveFactor"
                class="mt-6 grid gap-4 md:grid-cols-2"
            >

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Nama Faktor
                    </label>

                    <input
                        wire:model.blur="factor_name"
                        type="text"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    @error('factor_name')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Kode
                    </label>

                    <input
                        wire:model="factor_code"
                        type="text"
                        placeholder="otomatis jika kosong"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Sumber Nilai
                    </label>

                    <select
                        wire:model="factor_source_type"
                        class="w-full rounded-lg border
                            border-zinc-300 px-3 py-2
                            dark:border-zinc-700
                            dark:bg-zinc-800"
                    >
                        <option value="manual">
                            Manual oleh Pembina
                        </option>

                        <option value="attendance">
                            Otomatis dari Kehadiran
                        </option>
                    </select>

                    @error('factor_source_type')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Urutan
                    </label>

                    <input
                        wire:model="factor_sort_order"
                        type="number"
                        min="0"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                </div>


                <label
                    class="flex items-center gap-2
                           self-end pb-3"
                >
                    <input
                        wire:model="factor_is_active"
                        type="checkbox"
                    >

                    Aktif
                </label>


                <div class="md:col-span-2">

                    <label class="mb-1 block text-sm font-medium">
                        Keterangan
                    </label>

                    <textarea
                        wire:model="factor_description"
                        rows="2"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    ></textarea>

                </div>


                <div
                    class="flex gap-2 md:col-span-2"
                >

                    <button
                        type="submit"
                        class="rounded-lg bg-zinc-900
                               px-4 py-2 text-sm font-medium
                               text-white
                               dark:bg-white
                               dark:text-zinc-900"
                    >
                        {{ $editingFactorId
                            ? 'Simpan Perubahan'
                            : 'Tambah Faktor'
                        }}
                    </button>


                    @if ($editingFactorId)

                        <button
                            type="button"
                            wire:click="cancelFactorEdit"
                            class="rounded-lg border
                                   border-zinc-300
                                   px-4 py-2 text-sm"
                        >
                            Batal
                        </button>

                    @endif

                </div>

            </form>

        @endcan


        <div class="mt-6 overflow-x-auto">

            <table class="w-full text-left text-sm">

                <thead>
                    <tr class="border-b text-zinc-500">
                        <th class="p-3">Faktor</th>
                        <th class="p-3">Kode</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($factors as $factor)

                        <tr
                            wire:key="factor-{{ $factor->id }}"
                            class="border-b border-zinc-100
                                   dark:border-zinc-800"
                        >

                            <td class="p-3">
                                <div class="font-medium">
                                    {{ $factor->name }}
                                </div>

                                @if ($factor->description)

                                    <div class="text-xs text-zinc-500">
                                        {{ $factor->description }}
                                    </div>

                                @endif
                            </td>

                            <td class="p-3">
                                {{ $factor->code }}
                            </td>

                            <td class="p-3">
                                {{ $factor->is_active
                                    ? 'Aktif'
                                    : 'Nonaktif'
                                }}
                            </td>

                            <td class="p-3">

                                @can('assessment_factors.manage')

                                    <div class="flex gap-2">

                                        <button
                                            wire:click="
                                                editFactor(
                                                    {{ $factor->id }}
                                                )
                                            "
                                            type="button"
                                            class="rounded border
                                                   px-3 py-1.5"
                                        >
                                            Edit
                                        </button>

                                        <button
                                            wire:click="
                                                toggleFactor(
                                                    {{ $factor->id }}
                                                )
                                            "
                                            type="button"
                                            class="rounded border
                                                   px-3 py-1.5"
                                        >
                                            {{ $factor->is_active
                                                ? 'Nonaktifkan'
                                                : 'Aktifkan'
                                            }}
                                        </button>

                                    </div>

                                @endcan

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="4"
                                class="p-8 text-center
                                       text-zinc-500"
                            >
                                Belum ada faktor penilaian.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </section>


    {{-- =====================================================
    KONFIGURASI PENILAIAN
    ====================================================== --}}

    <section
        class="rounded-xl border border-zinc-200
               bg-white p-6 shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <h2 class="text-lg font-semibold">
            Konfigurasi Bobot
        </h2>

        <p class="mt-1 text-sm text-zinc-500">
            Total bobot harus tepat 100%.
        </p>


        @can('assessments.manage')

            <div
                class="mt-6 grid gap-4
                       md:grid-cols-3"
            >

                <div>

                    <label class="mb-1 block text-sm font-medium">
                        Tahun Ajaran
                    </label>

                    <select
                        wire:model.live="academic_year_id"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2"
                    >
                        <option value="">
                            -- Pilih --
                        </option>

                        @foreach ($academicYears as $year)

                            <option value="{{ $year->id }}">
                                {{ $year->name }}
                            </option>

                        @endforeach
                    </select>

                </div>


                <div>

                    <label class="mb-1 block text-sm font-medium">
                        Semester
                    </label>

                    <select
                        wire:model="semester_id"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2"
                    >
                        <option value="">
                            -- Pilih --
                        </option>

                        @foreach ($semesters as $semester)

                            <option value="{{ $semester->id }}">
                                {{ $semester->name }}
                            </option>

                        @endforeach
                    </select>

                </div>


                <div>

                    <label class="mb-1 block text-sm font-medium">
                        Nama Konfigurasi
                    </label>

                    <input
                        wire:model="config_name"
                        type="text"
                        placeholder="Contoh: Penilaian Semester Ganjil"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2"
                    >

                </div>

            </div>


            <button
                wire:click="createConfig"
                type="button"
                class="mt-4 rounded-lg
                       bg-zinc-900 px-4 py-2
                       text-sm font-medium text-white
                       dark:bg-white
                       dark:text-zinc-900"
            >
                Buat Konfigurasi
            </button>

        @endcan


        {{-- DAFTAR CONFIG --}}

        <div class="mt-6 space-y-3">

            @foreach ($configs as $config)

                <div
                    wire:key="config-{{ $config->id }}"
                    class="flex flex-col gap-3
                           rounded-lg border
                           border-zinc-200 p-4
                           md:flex-row
                           md:items-center
                           md:justify-between
                           dark:border-zinc-700"
                >

                    <div>

                        <div class="font-semibold">
                            {{ $config->name }}
                        </div>

                        <div
                            class="mt-1 text-sm
                                   text-zinc-500"
                        >
                            {{ $config->academicYear?->name }}
                            ·
                            {{ $config->semester?->name }}
                        </div>

                        <div
                            class="mt-1 text-sm
                                   text-zinc-500"
                        >
                            Total bobot:
                            {{ number_format(
                                $config->items->sum('weight'),
                                2
                            ) }}%
                        </div>

                    </div>


                    <div class="flex flex-wrap gap-2">

                        @if ($config->is_active)

                            <span
                                class="rounded-full
                                       bg-green-100
                                       px-3 py-1.5
                                       text-sm font-medium
                                       text-green-700"
                            >
                                Aktif
                            </span>

                        @endif


                        @can('assessments.manage')

                            <button
                                wire:click="
                                    editConfig(
                                        {{ $config->id }}
                                    )
                                "
                                type="button"
                                class="rounded-lg border
                                       px-3 py-1.5 text-sm"
                            >
                                Atur Bobot
                            </button>


                            @if (! $config->is_active)

                                <button
                                    wire:click="
                                        activateConfig(
                                            {{ $config->id }}
                                        )
                                    "
                                    wire:confirm="
                                        Aktifkan konfigurasi ini?
                                    "
                                    type="button"
                                    class="rounded-lg
                                           bg-green-600
                                           px-3 py-1.5
                                           text-sm text-white"
                                >
                                    Aktifkan
                                </button>

                            @endif

                        @endcan

                    </div>

                </div>

            @endforeach

        </div>

    </section>


    {{-- =====================================================
    EDIT BOBOT
    ====================================================== --}}

    @if ($editingConfig)

        <section
            class="rounded-xl border
                   border-zinc-200 bg-white
                   p-6 shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <h2 class="text-lg font-semibold">
                Bobot:
                {{ $editingConfig->name }}
            </h2>


            <div class="mt-6 space-y-3">

                @foreach (
                    $factors->where(
                        'is_active',
                        true
                    )
                    as $factor
                )

                    <div
                        class="grid items-center gap-3
                               md:grid-cols-[1fr_180px]"
                    >

                        <div>

                            <div class="font-medium">
                                {{ $factor->name }}
                            </div>

                            <div
                                class="text-xs
                                       text-zinc-500"
                            >
                                {{ $factor->code }}
                            </div>

                        </div>


                        <div class="flex items-center gap-2">

                            <input
                                wire:model="weights.{{ $factor->id }}"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                class="w-full rounded-lg
                                       border border-zinc-300
                                       px-3 py-2"
                            >

                            <span>%</span>

                        </div>

                    </div>

                @endforeach

            </div>


            @error('weights')

                <p class="mt-4 text-sm text-red-500">
                    {{ $message }}
                </p>

            @enderror


            <button
                wire:click="saveWeights"
                type="button"
                class="mt-6 rounded-lg
                       bg-zinc-900 px-4 py-2
                       text-sm font-medium
                       text-white
                       dark:bg-white
                       dark:text-zinc-900"
            >
                Simpan Bobot
            </button>

        </section>

    @endif

</div>