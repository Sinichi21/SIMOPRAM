<div class="space-y-6">

    @if (session('success'))

        <div
            class="rounded-lg border
                   border-green-200
                   bg-green-50 p-4
                   text-sm text-green-700
                   dark:border-green-900
                   dark:bg-green-950
                   dark:text-green-300"
        >
            {{ session('success') }}
        </div>

    @endif


    <div>

        <h1 class="text-2xl font-semibold">
            {{ $announcementId
                ? 'Edit Pengumuman'
                : 'Buat Pengumuman'
            }}
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            Buat informasi untuk siswa,
            pembina, kelas, atau Regu/Barung.
        </p>

    </div>


    <form
        wire:submit="save"
        class="rounded-xl border
               border-zinc-200 bg-white
               p-6 shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div>

            <label class="mb-1 block text-sm font-medium">
                Judul *
            </label>

            <input
                wire:model="title"
                type="text"
                class="w-full rounded-lg
                       border border-zinc-300
                       px-3 py-2
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >

            @error('title')
                <p class="mt-1 text-sm text-red-500">
                    {{ $message }}
                </p>
            @enderror

        </div>


        <div class="mt-4">

            <label class="mb-1 block text-sm font-medium">
                Isi Pengumuman *
            </label>

            <textarea
                wire:model="body"
                rows="8"
                class="w-full rounded-lg
                       border border-zinc-300
                       px-3 py-2
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            ></textarea>

            @error('body')
                <p class="mt-1 text-sm text-red-500">
                    {{ $message }}
                </p>
            @enderror

        </div>


        <div class="mt-6">

            <div class="font-medium">
                Target Penerima
            </div>

            <div
                class="mt-3 grid gap-3
                       md:grid-cols-2"
            >

                <label
                    class="flex items-center gap-3
                           rounded-lg border
                           border-zinc-200 p-3"
                >
                    <input
                        wire:model.live="target_types"
                        type="checkbox"
                        value="all_students"
                    >

                    Semua Siswa
                </label>


                <label
                    class="flex items-center gap-3
                           rounded-lg border
                           border-zinc-200 p-3"
                >
                    <input
                        wire:model.live="target_types"
                        type="checkbox"
                        value="all_coaches"
                    >

                    Semua Pembina
                </label>


                <label
                    class="flex items-center gap-3
                           rounded-lg border
                           border-zinc-200 p-3"
                >
                    <input
                        wire:model.live="target_types"
                        type="checkbox"
                        value="classroom"
                    >

                    Kelas Tertentu
                </label>


                <label
                    class="flex items-center gap-3
                           rounded-lg border
                           border-zinc-200 p-3"
                >
                    <input
                        wire:model.live="target_types"
                        type="checkbox"
                        value="scout_unit"
                    >

                    Regu / Barung Tertentu
                </label>

            </div>

            @error('target_types')
                <p class="mt-2 text-sm text-red-500">
                    {{ $message }}
                </p>
            @enderror

        </div>


        @if (
            in_array(
                'classroom',
                $target_types,
                true
            )
        )

            <div class="mt-5">

                <label class="mb-2 block text-sm font-medium">
                    Pilih Kelas
                </label>

                <div class="grid gap-2 md:grid-cols-3">

                    @foreach ($classrooms as $classroom)

                        <label
                            class="flex items-center
                                   gap-2 rounded-lg
                                   border border-zinc-200
                                   p-3"
                        >
                            <input
                                wire:model="classroom_ids"
                                type="checkbox"
                                value="{{ $classroom->id }}"
                            >

                            {{ $classroom->name }}
                        </label>

                    @endforeach

                </div>

                @error('classroom_ids')
                    <p class="mt-2 text-sm text-red-500">
                        {{ $message }}
                    </p>
                @enderror

            </div>

        @endif


        @if (
            in_array(
                'scout_unit',
                $target_types,
                true
            )
        )

            <div class="mt-5">

                <label class="mb-2 block text-sm font-medium">
                    Pilih Regu / Barung
                </label>

                <div class="grid gap-2 md:grid-cols-3">

                    @foreach ($scoutUnits as $unit)

                        <label
                            class="flex items-center
                                   gap-2 rounded-lg
                                   border border-zinc-200
                                   p-3"
                        >
                            <input
                                wire:model="scout_unit_ids"
                                type="checkbox"
                                value="{{ $unit->id }}"
                            >

                            {{ $unit->name }}
                        </label>

                    @endforeach

                </div>

            </div>

        @endif


        <div
            class="mt-6 grid gap-4
                   md:grid-cols-2"
        >

            <div>

                <label class="mb-1 block text-sm font-medium">
                    Jadwal Publikasi
                </label>

                <input
                    wire:model="publish_at"
                    type="datetime-local"
                    class="w-full rounded-lg
                           border border-zinc-300
                           px-3 py-2"
                >

                <p class="mt-1 text-xs text-zinc-500">
                    Kosongkan jika dipublikasikan manual.
                </p>

            </div>


            <div>

                <label class="mb-1 block text-sm font-medium">
                    Berlaku Sampai
                </label>

                <input
                    wire:model="expires_at"
                    type="datetime-local"
                    class="w-full rounded-lg
                           border border-zinc-300
                           px-3 py-2"
                >

            </div>

        </div>


        <label class="mt-5 flex items-center gap-2">

            <input
                wire:model="is_public"
                type="checkbox"
            >

            <span class="text-sm">
                Tampilkan pada halaman publik sekolah
            </span>

        </label>


        <div class="mt-6">

            <button
                type="submit"
                class="rounded-lg bg-zinc-900
                       px-4 py-2
                       text-sm font-medium
                       text-white
                       dark:bg-white
                       dark:text-zinc-900"
            >
                Simpan Pengumuman
            </button>

        </div>

    </form>


    @if ($announcement)

        <div
            class="rounded-xl border
                   border-zinc-200 bg-white
                   p-6 dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div class="flex flex-wrap gap-2">

                @if (
                    $announcement->status ===
                    'draft'
                )

                    @can('announcements.publish')

                        <button
                            wire:click="publish"
                            wire:confirm="
                                Publikasikan pengumuman ini?
                            "
                            type="button"
                            class="rounded-lg
                                   bg-green-600
                                   px-4 py-2
                                   text-sm font-medium
                                   text-white"
                        >
                            Publikasikan
                        </button>

                    @endcan

                @endif


                @if (
                    $announcement->status !==
                    'archived'
                )

                    @can('announcements.archive')

                        <button
                            wire:click="archive"
                            wire:confirm="
                                Arsipkan pengumuman ini?
                            "
                            type="button"
                            class="rounded-lg
                                   border border-zinc-300
                                   px-4 py-2
                                   text-sm"
                        >
                            Arsipkan
                        </button>

                    @endcan

                @endif

            </div>

        </div>

    @endif

</div>