<div class="space-y-6">

    {{-- Header --}}
    <div>
        <h1
            class="text-2xl font-semibold
                   text-zinc-900 dark:text-white"
        >
            Data Pembina
        </h1>

        <p
            class="mt-1 text-sm
                   text-zinc-500"
        >
            Kelola data pembina Pramuka
            pada sekolah aktif.
        </p>
    </div>


    {{-- Flash --}}
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


    {{-- Form --}}
    @canany([
        'coaches.create',
        'coaches.update'
    ])

        <form
            wire:submit="save"
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-6 shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="mb-5 flex
                       items-center justify-between"
            >

                <div>
                    <h2 class="text-lg font-semibold">
                        {{ $editingId
                            ? 'Edit Pembina'
                            : 'Tambah Pembina'
                        }}
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-zinc-500"
                    >
                        Masukkan identitas pembina
                        Pramuka.
                    </p>
                </div>

            </div>


            <div
                class="grid gap-4
                       md:grid-cols-2"
            >

                {{-- Nama --}}
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Nama Pembina
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        placeholder="Nama lengkap"
                        class="w-full rounded-lg
                               border border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    @error('name')
                        <p
                            class="mt-1 text-sm
                                   text-red-500"
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- NIP --}}
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        NIP / NTA
                    </label>

                    <input
                        type="text"
                        wire:model="nip"
                        placeholder="Opsional"
                        class="w-full rounded-lg
                               border border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    @error('nip')
                        <p
                            class="mt-1 text-sm
                                   text-red-500"
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- Gender --}}
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Jenis Kelamin
                    </label>

                    <select
                        wire:model="gender"
                        class="w-full rounded-lg
                               border border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >
                        <option value="">
                            -- Pilih --
                        </option>

                        <option value="L">
                            Laki-laki
                        </option>

                        <option value="P">
                            Perempuan
                        </option>
                    </select>

                    @error('gender')
                        <p
                            class="mt-1 text-sm
                                   text-red-500"
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- Telepon --}}
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Nomor Telepon
                    </label>

                    <input
                        type="text"
                        wire:model="phone"
                        placeholder="08xxxxxxxxxx"
                        class="w-full rounded-lg
                               border border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    @error('phone')
                        <p
                            class="mt-1 text-sm
                                   text-red-500"
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- Jabatan --}}
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Jabatan
                    </label>

                    <input
                        type="text"
                        wire:model="position"
                        placeholder="Contoh: Pembina Putra"
                        class="w-full rounded-lg
                               border border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    @error('position')
                        <p
                            class="mt-1 text-sm
                                   text-red-500"
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- Sertifikat --}}
                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Nomor Sertifikat
                    </label>

                    <input
                        type="text"
                        wire:model="certificate_number"
                        placeholder="KMD / KML / lainnya"
                        class="w-full rounded-lg
                               border border-zinc-300
                               px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    @error('certificate_number')
                        <p
                            class="mt-1 text-sm
                                   text-red-500"
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>


            <label
                class="mt-5 flex
                       items-center gap-2"
            >
                <input
                    type="checkbox"
                    wire:model="is_active"
                >

                <span class="text-sm">
                    Pembina aktif
                </span>
            </label>


            <div
                class="mt-6 flex gap-2"
            >

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="rounded-lg
                           bg-zinc-900
                           px-4 py-2
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
                        {{ $editingId
                            ? 'Simpan Perubahan'
                            : 'Tambah Pembina'
                        }}
                    </span>

                    <span
                        wire:loading
                        wire:target="save"
                    >
                        Menyimpan...
                    </span>
                </button>


                @if ($editingId)

                    <button
                        type="button"
                        wire:click="cancelEdit"
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

        </form>

    @endcanany


    {{-- Table --}}
    <div
        class="rounded-xl border
               border-zinc-200
               bg-white p-6 shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div
            class="mb-5 flex
                   flex-col gap-3
                   md:flex-row
                   md:items-center
                   md:justify-between"
        >

            <div>
                <h2 class="text-lg font-semibold">
                    Daftar Pembina
                </h2>

                <p
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    {{ $coaches->total() }}
                    pembina ditemukan.
                </p>
            </div>


            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari pembina..."
                class="rounded-lg border
                       border-zinc-300
                       px-3 py-2 text-sm
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-left text-sm">

                <thead>
                    <tr
                        class="border-b
                               border-zinc-200
                               text-zinc-500
                               dark:border-zinc-800"
                    >
                        <th class="p-3">
                            Nama
                        </th>

                        <th class="p-3">
                            NIP/NIK
                        </th>

                        <th class="p-3">
                            L/P
                        </th>

                        <th class="p-3">
                            Jabatan
                        </th>

                        <th class="p-3">
                            Telepon
                        </th>

                        <th class="p-3">
                            Status
                        </th>

                        <th class="p-3">
                            Aksi
                        </th>
                    </tr>
                </thead>


                <tbody>

                    @forelse (
                        $coaches as $coach
                    )

                        <tr
                            wire:key="coach-{{ $coach->id }}"
                            class="border-b
                                   border-zinc-100
                                   dark:border-zinc-800"
                        >

                            <td class="p-3">

                                <div class="font-medium">
                                    {{ $coach->name }}
                                </div>

                                @if (
                                    $coach->certificate_number
                                )
                                    <div
                                        class="mt-1 text-xs
                                               text-zinc-500"
                                    >
                                        {{ $coach->certificate_number }}
                                    </div>
                                @endif

                            </td>


                            <td class="p-3">
                                {{ $coach->nip ?: '-' }}
                            </td>


                            <td class="p-3">
                                {{ $coach->gender ?: '-' }}
                            </td>


                            <td class="p-3">
                                {{ $coach->position ?: '-' }}
                            </td>


                            <td class="p-3">
                                {{ $coach->phone ?: '-' }}
                            </td>


                            <td class="p-3">

                                @if ($coach->is_active)

                                    <span
                                        class="rounded-full
                                               bg-green-100
                                               px-2.5 py-1
                                               text-xs font-medium
                                               text-green-700
                                               dark:bg-green-950
                                               dark:text-green-300"
                                    >
                                        Aktif
                                    </span>

                                @else

                                    <span
                                        class="rounded-full
                                               bg-zinc-100
                                               px-2.5 py-1
                                               text-xs font-medium
                                               text-zinc-600
                                               dark:bg-zinc-800
                                               dark:text-zinc-300"
                                    >
                                        Nonaktif
                                    </span>

                                @endif

                            </td>


                            <td class="p-3">

                                <div class="flex gap-2">

                                    @can('coach_accounts.manage')
                                        <a
                                            href="{{ route('coach-accounts.manage', $coach->id) }}"
                                            wire:navigate
                                            class="rounded-lg border border-zinc-300 px-3 py-1.5 dark:border-zinc-700"
                                        >
                                            {{ $coach->user_id ? 'Kelola Akun' : 'Buat Akun' }}
                                        </a>
                                    @endcan

                                    @can('coaches.update')

                                        <button
                                            type="button"
                                            wire:click="edit({{ $coach->id }})"
                                            class="rounded-lg border
                                                   border-zinc-300
                                                   px-3 py-1.5
                                                   dark:border-zinc-700"
                                        >
                                            Edit
                                        </button>

                                    @endcan


                                    @can('coaches.toggle')

                                        <button
                                            type="button"
                                            wire:click="
                                                toggleStatus(
                                                    {{ $coach->id }}
                                                )
                                            "
                                            wire:confirm="
                                                {{ $coach->is_active
                                                    ? 'Nonaktifkan pembina ini?'
                                                    : 'Aktifkan pembina ini?'
                                                }}
                                            "
                                            class="rounded-lg border
                                                   border-zinc-300
                                                   px-3 py-1.5
                                                   dark:border-zinc-700"
                                        >
                                            {{ $coach->is_active
                                                ? 'Nonaktifkan'
                                                : 'Aktifkan'
                                            }}
                                        </button>

                                    @endcan

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="7"
                                class="p-8
                                       text-center
                                       text-zinc-500"
                            >
                                Belum ada data pembina.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="mt-4">
            {{ $coaches->links() }}
        </div>

    </div>

</div>
