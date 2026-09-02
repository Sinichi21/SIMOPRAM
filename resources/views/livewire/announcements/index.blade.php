<div class="space-y-6">

    <div
        class="flex items-center
               justify-between"
    >

        <div>

            <h1 class="text-2xl font-semibold">
                Pengumuman
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                Kelola informasi untuk anggota Pramuka.
            </p>

        </div>


        @can('announcements.create')

            <a
                href="{{ route(
                    'announcements.create'
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
                + Pengumuman
            </a>

        @endcan

    </div>


    <div
        class="rounded-xl border
               border-zinc-200
               bg-white p-6
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div class="mb-5 grid gap-3 md:grid-cols-2">

            <input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="Cari pengumuman..."
                class="rounded-lg border
                       border-zinc-300 px-3 py-2"
            >


            <select
                wire:model.live="status"
                class="rounded-lg border
                       border-zinc-300 px-3 py-2"
            >

                <option value="">
                    Semua Status
                </option>

                <option value="draft">
                    Draft
                </option>

                <option value="published">
                    Dipublikasikan
                </option>

                <option value="archived">
                    Arsip
                </option>

            </select>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-left text-sm">

                <thead>

                    <tr class="border-b text-zinc-500">

                        <th class="p-3">
                            Judul
                        </th>

                        <th class="p-3">
                            Dibuat Oleh
                        </th>

                        <th class="p-3">
                            Status
                        </th>

                        <th class="p-3">
                            Publikasi
                        </th>

                        <th class="p-3">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse (
                        $announcements
                        as $announcement
                    )

                        <tr
                            class="border-b
                                   border-zinc-100
                                   dark:border-zinc-800"
                        >

                            <td class="p-3">

                                <div class="font-medium">
                                    {{ $announcement->title }}
                                </div>

                            </td>


                            <td class="p-3">
                                {{ $announcement
                                    ->creator
                                    ?->name ?? '-'
                                }}
                            </td>


                            <td class="p-3">
                                {{ ucfirst(
                                    $announcement->status
                                ) }}
                            </td>


                            <td class="p-3">
                                {{ $announcement
                                    ->published_at
                                    ?->format(
                                        'd-m-Y H:i'
                                    ) ?? '-'
                                }}
                            </td>


                            <td class="p-3">

                                <a
                                    href="{{ route(
                                        'announcements.edit',
                                        $announcement->id
                                    ) }}"
                                    wire:navigate
                                    class="rounded-lg
                                           border
                                           border-zinc-300
                                           px-3 py-1.5"
                                >
                                    Kelola
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="p-8 text-center
                                       text-zinc-500"
                            >
                                Belum ada pengumuman.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="mt-4">
            {{ $announcements->links() }}
        </div>

    </div>

</div>