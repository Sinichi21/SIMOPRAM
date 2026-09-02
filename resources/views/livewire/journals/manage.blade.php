@php
    use Illuminate\Support\Facades\Storage;
@endphp

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


    {{-- HEADER --}}

    <div
        class="flex flex-col gap-4
               md:flex-row
               md:items-start
               md:justify-between"
    >

        <div>

            <h1 class="text-2xl font-semibold">
                Jurnal Kegiatan
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                {{ $activity->title }}
            </p>

            <p class="mt-1 text-sm text-zinc-500">

                {{ $activity->start_at
                    ->format('d-m-Y H:i')
                }}

                @if ($activity->location)
                    · {{ $activity->location }}
                @endif

            </p>

        </div>


        @if ($journal)

            <div>

                @if (
                    $journal->status ===
                    'published'
                )

                    <span
                        class="rounded-full
                               bg-green-100
                               px-3 py-1
                               text-sm font-medium
                               text-green-700"
                    >
                        Dipublikasikan
                    </span>

                @else

                    <span
                        class="rounded-full
                               bg-amber-100
                               px-3 py-1
                               text-sm font-medium
                               text-amber-700"
                    >
                        Draft
                    </span>

                @endif

            </div>

        @endif

    </div>


    {{-- INFORMASI AGENDA --}}

    <div
        class="grid gap-4
               md:grid-cols-3"
    >

        <div
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-4
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >
            <div class="text-xs text-zinc-500">
                Tahun Ajaran
            </div>

            <div class="mt-1 font-semibold">
                {{ $activity
                    ->academicYear
                    ?->name ?? '-'
                }}
            </div>
        </div>


        <div
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-4
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >
            <div class="text-xs text-zinc-500">
                Semester
            </div>

            <div class="mt-1 font-semibold">
                {{ $activity
                    ->semester
                    ?->name ?? '-'
                }}
            </div>
        </div>


        <div
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-4
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >
            <div class="text-xs text-zinc-500">
                Pembina
            </div>

            <div class="mt-1 font-semibold">
                {{ $activity
                    ->coaches
                    ->pluck('name')
                    ->join(', ')
                    ?: '-'
                }}
            </div>
        </div>

    </div>


    {{-- FORM --}}

    <form
        wire:submit="save"
        class="rounded-xl border
               border-zinc-200
               bg-white p-6
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div>

            <label class="mb-1 block text-sm font-medium">
                Sesi Absensi
            </label>

            <select
                wire:model.live="attendance_session_id"
                class="w-full rounded-lg
                       border border-zinc-300
                       px-3 py-2
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >
                <option value="">
                    -- Tidak menggunakan absensi --
                </option>

                @foreach (
                    $attendanceSessions as $session
                )

                    <option value="{{ $session->id }}">
                        {{ $session->name }}
                        -
                        {{ $session->open_at
                            ->format('d-m-Y H:i')
                        }}
                    </option>

                @endforeach

            </select>

            @error('attendance_session_id')
                <p class="mt-1 text-sm text-red-500">
                    {{ $message }}
                </p>
            @enderror

        </div>


        {{-- Statistik --}}

        @if ($attendance_session_id)

            <div
                class="mt-5 grid gap-3
                       sm:grid-cols-2
                       lg:grid-cols-7"
            >

                @php
                    $stats = [
                        'Peserta' =>
                            $attendanceStats[
                                'participants'
                            ],

                        'Hadir' =>
                            $attendanceStats[
                                'present'
                            ],

                        'Terlambat' =>
                            $attendanceStats[
                                'late'
                            ],

                        'Sakit' =>
                            $attendanceStats[
                                'sick'
                            ],

                        'Izin' =>
                            $attendanceStats[
                                'excused'
                            ],

                        'Alpa' =>
                            $attendanceStats[
                                'absent'
                            ],

                        'Belum Dicatat' =>
                            $attendanceStats[
                                'unrecorded'
                            ],
                    ];
                @endphp


                @foreach (
                    $stats as $label => $value
                )

                    <div
                        class="rounded-lg border
                               border-zinc-200
                               p-3 text-center
                               dark:border-zinc-700"
                    >

                        <div
                            class="text-2xl
                                   font-semibold"
                        >
                            {{ $value }}
                        </div>

                        <div
                            class="mt-1 text-xs
                                   text-zinc-500"
                        >
                            {{ $label }}
                        </div>

                    </div>

                @endforeach

            </div>

        @endif


        <div class="mt-6">

            <label class="mb-1 block text-sm font-medium">
                Tujuan Kegiatan
            </label>

            <textarea
                wire:model="objective"
                rows="3"
                class="w-full rounded-lg
                       border border-zinc-300
                       px-3 py-2
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            ></textarea>

        </div>


        <div class="mt-4">

            <label class="mb-1 block text-sm font-medium">
                Materi
            </label>

            <textarea
                wire:model="material"
                rows="4"
                class="w-full rounded-lg
                       border border-zinc-300
                       px-3 py-2
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            ></textarea>

        </div>


        <div class="mt-4">

            <label class="mb-1 block text-sm font-medium">
                Uraian Pelaksanaan *
            </label>

            <textarea
                wire:model="activity_description"
                rows="6"
                class="w-full rounded-lg
                       border border-zinc-300
                       px-3 py-2
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            ></textarea>

            @error('activity_description')
                <p class="mt-1 text-sm text-red-500">
                    {{ $message }}
                </p>
            @enderror

        </div>


        <div
            class="mt-4 grid gap-4
                   md:grid-cols-2"
        >

            <div>
                <label class="mb-1 block text-sm font-medium">
                    Hasil Kegiatan
                </label>

                <textarea
                    wire:model="result"
                    rows="4"
                    class="w-full rounded-lg
                           border border-zinc-300
                           px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-800"
                ></textarea>
            </div>


            <div>
                <label class="mb-1 block text-sm font-medium">
                    Evaluasi
                </label>

                <textarea
                    wire:model="evaluation"
                    rows="4"
                    class="w-full rounded-lg
                           border border-zinc-300
                           px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-800"
                ></textarea>
            </div>

        </div>


        <div
            class="mt-4 grid gap-4
                   md:grid-cols-2"
        >

            <div>
                <label class="mb-1 block text-sm font-medium">
                    Tindak Lanjut
                </label>

                <textarea
                    wire:model="follow_up"
                    rows="4"
                    class="w-full rounded-lg
                           border border-zinc-300
                           px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-800"
                ></textarea>
            </div>


            <div>
                <label class="mb-1 block text-sm font-medium">
                    Catatan
                </label>

                <textarea
                    wire:model="notes"
                    rows="4"
                    class="w-full rounded-lg
                           border border-zinc-300
                           px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-800"
                ></textarea>
            </div>

        </div>


        {{-- Upload --}}

        @can('journals.attachments')

            <div class="mt-6">

                <label class="mb-1 block text-sm font-medium">
                    Lampiran
                </label>

                <input
                    type="file"
                    wire:model="attachments"
                    multiple
                    accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                    class="block w-full text-sm"
                >

                <p class="mt-1 text-xs text-zinc-500">
                    Maksimal 10 file,
                    masing-masing maksimal 5 MB.
                </p>

                @error('attachments.*')
                    <p class="mt-1 text-sm text-red-500">
                        {{ $message }}
                    </p>
                @enderror

            </div>

        @endcan


        <div class="mt-6 flex gap-2">

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
                Simpan Jurnal
            </button>

        </div>

    </form>


    {{-- LAMPIRAN TERSIMPAN --}}

    @if (
        $journal &&
        $journal->attachments->isNotEmpty()
    )

        <div
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-6
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <h2 class="mb-4 text-lg font-semibold">
                Lampiran
            </h2>


            <div class="space-y-2">

                @foreach (
                    $journal->attachments
                    as $attachment
                )

                    <div
                        class="flex items-center
                               justify-between
                               rounded-lg border
                               border-zinc-200
                               p-3
                               dark:border-zinc-700"
                    >

                        <div>

                            <a
                                href="{{ Storage::disk('public')
                                    ->url($attachment->path)
                                }}"
                                target="_blank"
                                class="font-medium underline"
                            >
                                {{ $attachment->original_name }}
                            </a>

                            <div
                                class="mt-1 text-xs
                                       text-zinc-500"
                            >
                                {{ number_format(
                                    $attachment->size_bytes
                                    / 1024,
                                    1
                                ) }}
                                KB
                            </div>

                        </div>


                        @can('journals.attachments')

                            <button
                                type="button"
                                wire:click="
                                    deleteAttachment(
                                        {{ $attachment->id }}
                                    )
                                "
                                wire:confirm="
                                    Hapus lampiran ini?
                                "
                                class="text-sm text-red-600"
                            >
                                Hapus
                            </button>

                        @endcan

                    </div>

                @endforeach

            </div>

        </div>

    @endif


    {{-- PUBLIKASI --}}

    @if ($journal)

        @can('journals.publish')

            <div
                class="rounded-xl border
                       border-zinc-200
                       bg-white p-6
                       dark:border-zinc-800
                       dark:bg-zinc-900"
            >

                @if (
                    $journal->status ===
                    'draft'
                )

                    <button
                        type="button"
                        wire:click="publish"
                        wire:confirm="
                            Publikasikan jurnal ini?
                        "
                        class="rounded-lg
                               bg-green-600
                               px-4 py-2
                               text-sm font-medium
                               text-white"
                    >
                        Publikasikan Jurnal
                    </button>

                @else

                    <button
                        type="button"
                        wire:click="returnToDraft"
                        wire:confirm="
                            Kembalikan jurnal ke draft?
                        "
                        class="rounded-lg border
                               border-zinc-300
                               px-4 py-2
                               text-sm"
                    >
                        Kembalikan ke Draft
                    </button>

                @endif

            </div>

        @endcan

    @endif

</div>