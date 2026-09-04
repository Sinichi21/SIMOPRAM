<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-semibold">
            Jurnal Kegiatan
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            Dokumentasi pelaksanaan kegiatan Pramuka.
        </p>
    </div>


    <div
        class="rounded-xl border border-zinc-200
               bg-white p-6 shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div
            class="mb-5 grid gap-3
                   md:grid-cols-3"
        >

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari kegiatan..."
                class="rounded-lg border
                       border-zinc-300 px-3 py-2
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >

            <select
                wire:model.live="status"
                class="rounded-lg border
                       border-zinc-300 px-3 py-2
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >
                <option value="">
                    Semua
                </option>

                <option value="none">
                    Belum Ada Jurnal
                </option>

                <option value="draft">
                    Draft
                </option>

                <option value="published">
                    Dipublikasikan
                </option>
            </select>

            <select
                wire:model.live="scoutLevelId"
                class="rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
            >
                <option value="">Semua Golongan</option>

                @foreach ($scoutLevels as $scoutLevel)
                    <option value="{{ $scoutLevel->id }}">
                        {{ $scoutLevel->name }}
                    </option>
                @endforeach
            </select>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-left text-sm">

                <thead>
                    <tr
                        class="border-b border-zinc-200
                               text-zinc-500
                               dark:border-zinc-800"
                    >
                        <th class="p-3">
                            Kegiatan
                        </th>

                        <th class="p-3">
                            Tanggal
                        </th>

                        <th class="p-3">
                            Pembina
                        </th>

                        <th class="p-3">
                            Absensi
                        </th>

                        <th class="p-3">
                            Jurnal
                        </th>

                        <th class="p-3">
                            Aksi
                        </th>
                    </tr>
                </thead>


                <tbody>

                    @forelse ($activities as $activity)

                        <tr
                            wire:key="journal-activity-{{ $activity->id }}"
                            class="border-b border-zinc-100
                                   dark:border-zinc-800"
                        >

                            <td class="p-3">

                                <div class="font-medium">
                                    {{ $activity->title }}

                                    <div class="text-xs font-normal text-zinc-500">
                                        {{ $activity->scoutLevels->pluck('name')->join(', ') ?: 'Semua Golongan' }}
                                    </div>
                                </div>

                                <div class="text-xs text-zinc-500">
                                    {{ $activity->location ?: '-' }}
                                </div>

                            </td>


                            <td class="p-3">
                                {{ $activity->start_at
                                    ->format('d-m-Y H:i')
                                }}
                            </td>


                            <td class="p-3">
                                {{ $activity->coaches
                                    ->pluck('name')
                                    ->join(', ')
                                    ?: '-'
                                }}
                            </td>


                            <td class="p-3">
                                {{ $activity->attendance_sessions_count }}
                                sesi
                            </td>


                            <td class="p-3">

                                @if (! $activity->journal)

                                    <span class="text-zinc-500">
                                        Belum dibuat
                                    </span>

                                @elseif (
                                    $activity->journal->status ===
                                    'published'
                                )

                                    <span class="font-medium text-green-600">
                                        Dipublikasikan
                                    </span>

                                @else

                                    <span class="font-medium text-amber-600">
                                        Draft
                                    </span>

                                @endif

                            </td>


                            <td class="p-3">

                                <a
                                    href="{{ route(
                                        'journals.manage',
                                        $activity->id
                                    ) }}"
                                    wire:navigate
                                    class="rounded-lg
                                           bg-zinc-900
                                           px-3 py-1.5
                                           text-sm text-white
                                           dark:bg-white
                                           dark:text-zinc-900"
                                >
                                    {{ $activity->journal
                                        ? 'Kelola'
                                        : 'Buat Jurnal'
                                    }}
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="6"
                                class="p-8 text-center
                                       text-zinc-500"
                            >
                                Belum ada kegiatan.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="mt-4">
            {{ $activities->links() }}
        </div>

    </div>

</div>
