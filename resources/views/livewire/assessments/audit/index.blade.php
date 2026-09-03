<div class="space-y-6">

    {{-- =========================================================
    HEADER
    ========================================================== --}}

    <div>

        <h1 class="text-2xl font-semibold">
            Audit Penilaian
        </h1>

        <p
            class="mt-1 max-w-3xl
                   text-sm leading-6
                   text-zinc-500"
        >
            Riwayat perubahan penting pada penilaian,
            bobot, nilai kegiatan, dan proses
            sinkronisasi.
        </p>

    </div>


    {{-- =========================================================
    FILTER
    ========================================================== --}}

    <section
        class="rounded-xl border
               border-zinc-200
               bg-white p-5
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div
            class="grid gap-4
                   md:grid-cols-2
                   xl:grid-cols-5"
        >

            <div>

                <label
                    class="mb-1 block
                           text-sm font-medium"
                >
                    Pencarian
                </label>

                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari pengguna, aksi..."
                    class="w-full rounded-lg
                           border border-zinc-300
                           bg-white px-3 py-2
                           text-sm
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                >

            </div>


            <div>

                <label
                    class="mb-1 block
                           text-sm font-medium"
                >
                    Modul
                </label>

                <select
                    wire:model.live="module"
                    class="w-full rounded-lg
                           border border-zinc-300
                           bg-white px-3 py-2
                           text-sm
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                >

                    <option value="">
                        Semua Modul
                    </option>

                    @foreach (
                        $modules
                        as $moduleOption
                    )

                        <option
                            value="{{ $moduleOption }}"
                        >
                            {{ $moduleOption }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div>

                <label
                    class="mb-1 block
                           text-sm font-medium"
                >
                    Aksi
                </label>

                <select
                    wire:model.live="action"
                    class="w-full rounded-lg
                           border border-zinc-300
                           bg-white px-3 py-2
                           text-sm
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                >

                    <option value="">
                        Semua Aksi
                    </option>

                    @foreach (
                        $actions
                        as $actionOption
                    )

                        <option
                            value="{{ $actionOption }}"
                        >
                            {{ $actionOption }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div>

                <label
                    class="mb-1 block
                           text-sm font-medium"
                >
                    Dari
                </label>

                <input
                    type="date"
                    wire:model.live="dateFrom"
                    class="w-full rounded-lg
                           border border-zinc-300
                           bg-white px-3 py-2
                           text-sm
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                >

            </div>


            <div>

                <label
                    class="mb-1 block
                           text-sm font-medium"
                >
                    Sampai
                </label>

                <input
                    type="date"
                    wire:model.live="dateTo"
                    class="w-full rounded-lg
                           border border-zinc-300
                           bg-white px-3 py-2
                           text-sm
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                >

            </div>

        </div>


        <div
            class="mt-4 flex justify-end"
        >

            <button
                type="button"
                wire:click="resetFilters"
                class="rounded-lg border
                       border-zinc-300
                       px-4 py-2
                       text-sm font-medium
                       dark:border-zinc-700"
            >
                Reset Filter
            </button>

        </div>

    </section>


    {{-- =========================================================
    TABLE
    ========================================================== --}}

    <section
        class="overflow-hidden
               rounded-xl border
               border-zinc-200
               bg-white
               shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead
                    class="bg-zinc-50
                           dark:bg-zinc-950"
                >

                    <tr>

                        <th
                            class="px-4 py-3
                                   text-left"
                        >
                            Waktu
                        </th>

                        <th
                            class="px-4 py-3
                                   text-left"
                        >
                            Pengguna
                        </th>

                        <th
                            class="px-4 py-3
                                   text-left"
                        >
                            Modul
                        </th>

                        <th
                            class="px-4 py-3
                                   text-left"
                        >
                            Aksi
                        </th>

                        <th
                            class="px-4 py-3
                                   text-left"
                        >
                            Keterangan
                        </th>

                        <th
                            class="px-4 py-3
                                   text-left"
                        >
                            Perubahan
                        </th>

                    </tr>

                </thead>


                <tbody
                    class="divide-y
                           divide-zinc-200
                           dark:divide-zinc-800"
                >

                    @forelse (
                        $logs
                        as $log
                    )

                        <tr
                            wire:key="audit-log-{{ $log->id }}"
                            class="align-top"
                        >

                            <td
                                class="whitespace-nowrap
                                       px-4 py-4"
                            >

                                <div class="font-medium">
                                    {{ $log
                                        ->created_at
                                        ?->format(
                                            'd/m/Y'
                                        ) }}
                                </div>

                                <div
                                    class="mt-1 text-xs
                                           text-zinc-500"
                                >
                                    {{ $log
                                        ->created_at
                                        ?->format(
                                            'H:i:s'
                                        ) }}
                                </div>

                            </td>


                            <td class="px-4 py-4">

                                <div class="font-medium">
                                    {{ $log
                                        ->user
                                        ?->name
                                        ?? 'Sistem' }}
                                </div>

                                @if (
                                    $log->user_id
                                )

                                    <div
                                        class="mt-1 text-xs
                                               text-zinc-500"
                                    >
                                        User #{{ $log->user_id }}
                                    </div>

                                @endif

                            </td>


                            <td class="px-4 py-4">

                                <span
                                    class="rounded-full
                                           bg-zinc-100
                                           px-2.5 py-1
                                           text-xs font-medium
                                           dark:bg-zinc-800"
                                >
                                    {{ $log->module ?? '-' }}
                                </span>

                            </td>


                            <td class="px-4 py-4">

                                <code
                                    class="text-xs
                                           text-zinc-700
                                           dark:text-zinc-300"
                                >
                                    {{ $log->action }}
                                </code>

                            </td>


                            <td
                                class="max-w-sm
                                       px-4 py-4"
                            >
                                {{ $log->description ?? '-' }}
                            </td>


                            <td
                                class="min-w-[320px]
                                       px-4 py-4"
                            >

                                @if (
                                    $log->old_values
                                    ||
                                    $log->new_values
                                )

                                    <details>

                                        <summary
                                            class="cursor-pointer
                                                   text-sm
                                                   font-medium"
                                        >
                                            Lihat perubahan
                                        </summary>


                                        <div
                                            class="mt-3 grid
                                                   gap-3"
                                        >

                                            @if (
                                                $log->old_values
                                            )

                                                <div>
                                                    <div
                                                        class="mb-1
                                                               text-xs
                                                               font-medium
                                                               text-zinc-500"
                                                    >
                                                        Sebelum
                                                    </div>

                                                    <pre
                                                        class="overflow-x-auto
                                                               rounded-lg
                                                               bg-zinc-100
                                                               p-3
                                                               text-xs
                                                               dark:bg-zinc-950"
                                                    >{{ json_encode(
                                                        $log->old_values,
                                                        JSON_PRETTY_PRINT
                                                        |
                                                        JSON_UNESCAPED_UNICODE
                                                    ) }}</pre>
                                                </div>

                                            @endif


                                            @if (
                                                $log->new_values
                                            )

                                                <div>
                                                    <div
                                                        class="mb-1
                                                               text-xs
                                                               font-medium
                                                               text-zinc-500"
                                                    >
                                                        Sesudah
                                                    </div>

                                                    <pre
                                                        class="overflow-x-auto
                                                               rounded-lg
                                                               bg-zinc-100
                                                               p-3
                                                               text-xs
                                                               dark:bg-zinc-950"
                                                    >{{ json_encode(
                                                        $log->new_values,
                                                        JSON_PRETTY_PRINT
                                                        |
                                                        JSON_UNESCAPED_UNICODE
                                                    ) }}</pre>
                                                </div>

                                            @endif

                                        </div>

                                    </details>

                                @else

                                    <span
                                        class="text-zinc-400"
                                    >
                                        -
                                    </span>

                                @endif

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
                                Belum ada riwayat audit
                                yang sesuai dengan filter.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if (
            $logs->hasPages()
        )

            <div
                class="border-t
                       border-zinc-200
                       p-4
                       dark:border-zinc-800"
            >
                {{ $logs->links() }}
            </div>

        @endif

    </section>

</div>