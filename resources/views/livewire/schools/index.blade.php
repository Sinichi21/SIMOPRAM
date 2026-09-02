<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex items-start justify-between gap-4">

        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">
                Data Sekolah
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                Kelola sekolah yang menggunakan SIMOPRAM.
            </p>
        </div>

    </div>


    {{-- FLASH MESSAGE --}}
    @if (session('success'))

        <div
            class="rounded-lg border border-green-200
                   bg-green-50 p-4 text-sm text-green-700
                   dark:border-green-900 dark:bg-green-950
                   dark:text-green-300"
        >
            {{ session('success') }}
        </div>

    @endif


    {{-- FORM --}}
    @canany([
        'schools.create',
        'schools.update'
    ])

        <form
            wire:submit="save"
            class="rounded-xl border border-zinc-200
                   bg-white p-6 shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <h2 class="mb-5 text-lg font-semibold">

                {{ $editingId
                    ? 'Edit Sekolah'
                    : 'Tambah Sekolah'
                }}

            </h2>


            <div
                class="grid gap-4
                       md:grid-cols-2"
            >

                {{-- NPSN --}}
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        NPSN
                    </label>

                    <input
                        type="text"
                        wire:model="npsn"
                        placeholder="Contoh: 50103123"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    @error('npsn')
                        <span class="text-sm text-red-500">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                {{-- NAMA --}}
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Nama Sekolah
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        placeholder="SD Negeri 16 Pemecutan"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    @error('name')
                        <span class="text-sm text-red-500">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                {{-- JENJANG --}}
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Jenjang
                    </label>

                    <select
                        wire:model="level"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                        <option value="">
                            Pilih jenjang
                        </option>

                        <option value="SD">SD</option>
                        <option value="SMP">SMP</option>
                        <option value="SMA">SMA</option>
                        <option value="SMK">SMK</option>

                        <option value="MI">MI</option>
                        <option value="MTs">MTs</option>
                        <option value="MA">MA</option>

                        <option value="Lainnya">
                            Lainnya
                        </option>

                    </select>

                    @error('level')
                        <span class="text-sm text-red-500">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                {{-- TELEPON --}}
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Telepon
                    </label>

                    <input
                        type="text"
                        wire:model="phone"
                        placeholder="0361..."
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                </div>


                {{-- EMAIL --}}
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Email
                    </label>

                    <input
                        type="email"
                        wire:model="email"
                        placeholder="sekolah@example.com"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    @error('email')
                        <span class="text-sm text-red-500">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                {{-- WEBSITE --}}
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Website
                    </label>

                    <input
                        type="url"
                        wire:model="website"
                        placeholder="https://..."
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                    @error('website')
                        <span class="text-sm text-red-500">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                {{-- DESA --}}
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Desa / Kelurahan
                    </label>

                    <input
                        type="text"
                        wire:model="village"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                </div>


                {{-- KECAMATAN --}}
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Kecamatan
                    </label>

                    <input
                        type="text"
                        wire:model="district"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                </div>


                {{-- KOTA --}}
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Kabupaten / Kota
                    </label>

                    <input
                        type="text"
                        wire:model="city"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                </div>


                {{-- PROVINSI --}}
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Provinsi
                    </label>

                    <input
                        type="text"
                        wire:model="province"
                        value="Bali"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                </div>


                {{-- KODE POS --}}
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Kode Pos
                    </label>

                    <input
                        type="text"
                        wire:model="postal_code"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                </div>


                {{-- TIMEZONE --}}
                <div>

                    <label
                        class="mb-1 block text-sm font-medium"
                    >
                        Zona Waktu
                    </label>

                    <select
                        wire:model="timezone"
                        class="w-full rounded-lg border
                               border-zinc-300 px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-800"
                    >

                        <option value="Asia/Jakarta">
                            WIB
                        </option>

                        <option value="Asia/Makassar">
                            WITA
                        </option>

                        <option value="Asia/Jayapura">
                            WIT
                        </option>

                    </select>

                </div>

            </div>


            {{-- ALAMAT --}}
            <div class="mt-4">

                <label
                    class="mb-1 block text-sm font-medium"
                >
                    Alamat
                </label>

                <textarea
                    wire:model="address"
                    rows="3"
                    class="w-full rounded-lg border
                           border-zinc-300 px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-800"
                ></textarea>

            </div>


            {{-- STATUS --}}
            <label
                class="mt-4 flex items-center gap-2"
            >

                <input
                    type="checkbox"
                    wire:model="is_active"
                >

                <span class="text-sm">
                    Sekolah aktif
                </span>

            </label>


            {{-- BUTTON --}}
            <div class="mt-6 flex gap-2">

                <button
                    type="submit"
                    class="rounded-lg bg-zinc-900
                           px-4 py-2 text-sm font-medium
                           text-white
                           dark:bg-white
                           dark:text-zinc-900"
                >
                    {{ $editingId
                        ? 'Simpan Perubahan'
                        : 'Tambah Sekolah'
                    }}
                </button>

                @if ($editingId)

                    <button
                        type="button"
                        wire:click="cancelEdit"
                        class="rounded-lg border
                               border-zinc-300
                               px-4 py-2 text-sm
                               dark:border-zinc-700"
                    >
                        Batal
                    </button>

                @endif

            </div>

        </form>

    @endcanany


    {{-- DAFTAR SEKOLAH --}}
    <div
        class="rounded-xl border
               border-zinc-200 bg-white p-6 shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div
            class="mb-5 flex flex-col
                   justify-between gap-3
                   md:flex-row md:items-center"
        >

            <div>
                <h2 class="text-lg font-semibold">
                    Daftar Sekolah
                </h2>

                <p class="text-sm text-zinc-500">
                    Pilih Kelola untuk masuk ke tenant sekolah.
                </p>
            </div>

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari sekolah / NPSN..."
                class="rounded-lg border
                       border-zinc-300
                       px-3 py-2 text-sm
                       dark:border-zinc-700
                       dark:bg-zinc-800"
            >

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-left">

                <thead>

                    <tr
                        class="border-b
                               border-zinc-200
                               text-sm text-zinc-500
                               dark:border-zinc-800"
                    >
                        <th class="p-3">Sekolah</th>
                        <th class="p-3">NPSN</th>
                        <th class="p-3">Jenjang</th>
                        <th class="p-3">Kab/Kota</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Aksi</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse ($schools as $school)

                        <tr
                            wire:key="school-{{ $school->id }}"
                            class="border-b
                                   border-zinc-100
                                   dark:border-zinc-800"
                        >

                            <td class="p-3">

                                <div class="font-medium">
                                    {{ $school->name }}
                                </div>

                                @if (
                                    (int) session('active_school_id')
                                    ===
                                    (int) $school->id
                                )

                                    <div
                                        class="mt-1 text-xs
                                               font-medium
                                               text-green-600"
                                    >
                                        Sedang dikelola
                                    </div>

                                @endif

                            </td>

                            <td class="p-3">
                                {{ $school->npsn }}
                            </td>

                            <td class="p-3">
                                {{ $school->level ?: '-' }}
                            </td>

                            <td class="p-3">
                                {{ $school->city ?: '-' }}
                            </td>

                            <td class="p-3">

                                @if ($school->is_active)

                                    <span
                                        class="rounded-full
                                               bg-green-100
                                               px-2.5 py-1
                                               text-xs font-medium
                                               text-green-700"
                                    >
                                        Aktif
                                    </span>

                                @else

                                    <span
                                        class="rounded-full
                                               bg-zinc-100
                                               px-2.5 py-1
                                               text-xs font-medium
                                               text-zinc-600"
                                    >
                                        Nonaktif
                                    </span>

                                @endif

                            </td>

                            <td class="p-3">

                                <div
                                    class="flex flex-wrap gap-2"
                                >

                                    {{-- KELOLA SEKOLAH --}}
                                    @if ($school->is_active)

                                        <form
                                            method="POST"
                                            action="{{ route('school.switch') }}"
                                        >
                                            @csrf

                                            <input
                                                type="hidden"
                                                name="school_id"
                                                value="{{ $school->id }}"
                                            >

                                            <button
                                                type="submit"
                                                class="rounded-lg
                                                       bg-zinc-900
                                                       px-3 py-1.5
                                                       text-sm
                                                       text-white
                                                       dark:bg-white
                                                       dark:text-zinc-900"
                                            >
                                                Kelola
                                            </button>

                                        </form>

                                    @endif


                                    {{-- EDIT --}}
                                    @can('schools.update')

                                        <button
                                            type="button"
                                            wire:click="edit({{ $school->id }})"
                                            class="rounded-lg border
                                                   border-zinc-300
                                                   px-3 py-1.5
                                                   text-sm
                                                   dark:border-zinc-700"
                                        >
                                            Edit
                                        </button>

                                    @endcan


                                    {{-- STATUS --}}
                                    @can('schools.toggle')

                                        <button
                                            type="button"
                                            wire:click="toggleStatus({{ $school->id }})"
                                            wire:confirm="
                                                {{ $school->is_active
                                                    ? 'Nonaktifkan sekolah ini?'
                                                    : 'Aktifkan sekolah ini?'
                                                }}
                                            "
                                            class="rounded-lg border
                                                   border-zinc-300
                                                   px-3 py-1.5
                                                   text-sm
                                                   dark:border-zinc-700"
                                        >
                                            {{ $school->is_active
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
                                colspan="6"
                                class="p-8 text-center
                                       text-zinc-500"
                            >
                                Belum ada sekolah.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div class="mt-4">
            {{ $schools->links() }}
        </div>

    </div>

</div>