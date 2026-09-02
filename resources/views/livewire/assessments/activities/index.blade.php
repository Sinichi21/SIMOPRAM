<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-semibold">
            Penilaian Kegiatan
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            Buat form penilaian untuk kegiatan Pramuka
            dengan mode individu maupun regu.
        </p>
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


    @can('activity_assessments.create')

        <form
            wire:submit="create"
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-6
                   shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <h2 class="text-lg font-semibold">
                Buat Form Penilaian
            </h2>


            <div
                class="mt-5 grid gap-4
                       md:grid-cols-2"
            >

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Kegiatan
                    </label>

                    <select
                        wire:model="activityId"
                        class="w-full rounded-lg
                               border border-zinc-300
                               bg-white px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-950"
                    >
                        <option value="">
                            -- Pilih Kegiatan --
                        </option>

                        @foreach ($activities as $activity)

                            <option value="{{ $activity->id }}">
                                {{ $activity->title }}

                                @if ($activity->start_at)
                                    -
                                    {{ $activity->start_at
                                        ->format('d-m-Y') }}
                                @endif
                            </option>

                        @endforeach
                    </select>

                    @error('activityId')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Faktor Tujuan
                    </label>

                    <select
                        wire:model="assessmentFactorId"
                        class="w-full rounded-lg
                               border border-zinc-300
                               bg-white px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-950"
                    >
                        <option value="">
                            -- Pilih Faktor --
                        </option>

                        @foreach ($factors as $factor)

                            <option value="{{ $factor->id }}">
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
                    <label class="mb-1 block text-sm font-medium">
                        Nama Form
                    </label>

                    <input
                        type="text"
                        wire:model="title"
                        placeholder="Contoh: Praktik Tali-Temali"
                        class="w-full rounded-lg
                               border border-zinc-300
                               bg-white px-3 py-2
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
                    <label class="mb-1 block text-sm font-medium">
                        Mode Penilaian
                    </label>

                    <select
                        wire:model="mode"
                        class="w-full rounded-lg
                               border border-zinc-300
                               bg-white px-3 py-2
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

                <label class="mb-1 block text-sm font-medium">
                    Deskripsi
                </label>

                <textarea
                    wire:model="description"
                    rows="3"
                    class="w-full rounded-lg
                           border border-zinc-300
                           bg-white px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                ></textarea>

            </div>


            <div class="mt-5 flex justify-end">

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="create"
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
                        wire:target="create"
                    >
                        Buat Form
                    </span>

                    <span
                        wire:loading
                        wire:target="create"
                    >
                        Membuat...
                    </span>
                </button>

            </div>

        </form>

    @endcan


    <section
        class="rounded-xl border
               border-zinc-200
               bg-white
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div
            class="flex flex-col gap-3
                   border-b border-zinc-200
                   p-5
                   sm:flex-row
                   sm:items-center
                   sm:justify-between
                   dark:border-zinc-800"
        >

            <h2 class="font-semibold">
                Daftar Form Penilaian
            </h2>


            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari form..."
                class="rounded-lg border
                       border-zinc-300
                       bg-white px-3 py-2
                       text-sm
                       dark:border-zinc-700
                       dark:bg-zinc-950"
            >

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead
                    class="bg-zinc-50
                           dark:bg-zinc-950"
                >
                    <tr>
                        <th class="px-4 py-3 text-left">
                            Form
                        </th>

                        <th class="px-4 py-3 text-left">
                            Kegiatan
                        </th>

                        <th class="px-4 py-3 text-left">
                            Faktor
                        </th>

                        <th class="px-4 py-3 text-center">
                            Mode
                        </th>

                        <th class="px-4 py-3 text-center">
                            Status
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
                        $assessments
                        as $assessment
                    )

                        <tr>
                            <td class="px-4 py-3 font-medium">
                                {{ $assessment->title }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $assessment->activity?->title ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $assessment->factor?->name ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                {{ $assessment->mode === 'individual'
                                    ? 'Individu'
                                    : 'Regu' }}
                            </td>

                            <td class="px-4 py-3 text-center">

                                @if ($assessment->status === 'published')

                                    <span
                                        class="rounded-full
                                               bg-green-100
                                               px-2 py-1
                                               text-xs font-medium
                                               text-green-700
                                               dark:bg-green-950
                                               dark:text-green-300"
                                    >
                                        Published
                                    </span>

                                @else

                                    <span
                                        class="rounded-full
                                               bg-zinc-100
                                               px-2 py-1
                                               text-xs font-medium
                                               text-zinc-700
                                               dark:bg-zinc-800
                                               dark:text-zinc-300"
                                    >
                                        Draft
                                    </span>

                                @endif

                            </td>

                            <td class="px-4 py-3 text-right">

                                <a
                                    href="{{ route(
                                        'activity-assessments.edit',
                                        $assessment
                                    ) }}"
                                    wire:navigate
                                    class="font-medium
                                           text-zinc-700
                                           hover:underline
                                           dark:text-zinc-200"
                                >
                                    Kelola
                                </a>

                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="6"
                                class="px-4 py-8
                                       text-center
                                       text-zinc-500"
                            >
                                Belum ada form penilaian kegiatan.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </section>

</div>