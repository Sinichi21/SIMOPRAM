<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-semibold">
            Pengaturan Dokumen Sekolah
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            Informasi ini digunakan pada laporan
            dan dokumen resmi SIMOPRAM.
        </p>
    </div>


    @if (session('status'))

        <div
            class="rounded-lg border
                   border-green-200
                   bg-green-50
                   px-4 py-3
                   text-sm
                   text-green-700
                   dark:border-green-900
                   dark:bg-green-950
                   dark:text-green-300"
        >
            {{ session('status') }}
        </div>

    @endif


    <form
        wire:submit="save"
        class="space-y-6"
    >

        {{-- KEPALA SEKOLAH --}}

        <section
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-6
                   shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <h2 class="text-lg font-semibold">
                Kepala Sekolah
            </h2>

            <p class="mt-1 text-sm text-zinc-500">
                Digunakan pada bagian tanda tangan
                dokumen resmi.
            </p>


            <div
                class="mt-5 grid gap-4
                       md:grid-cols-2"
            >

                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Nama Kepala Sekolah
                    </label>

                    <input
                        type="text"
                        wire:model="principalName"
                        class="w-full rounded-lg
                               border border-zinc-300
                               bg-white px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-950"
                    >

                    @error('principalName')
                        <div class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </div>
                    @enderror
                </div>


                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        NIP Kepala Sekolah
                    </label>

                    <input
                        type="text"
                        wire:model="principalNip"
                        class="w-full rounded-lg
                               border border-zinc-300
                               bg-white px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-950"
                    >

                    @error('principalNip')
                        <div class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

            </div>

        </section>


        {{-- PEMBINA --}}

        <section
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-6
                   shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <h2 class="text-lg font-semibold">
                Pembina Penanggung Jawab
            </h2>


            <div class="mt-5">

                <label
                    class="mb-1 block
                           text-sm font-medium"
                >
                    Pembina Pramuka
                </label>

                <select
                    wire:model="responsibleCoachId"
                    class="w-full rounded-lg
                           border border-zinc-300
                           bg-white px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-950"
                >
                    <option value="">
                        -- Pilih Pembina --
                    </option>

                    @foreach ($coaches as $coach)

                        <option value="{{ $coach->id }}">
                            {{ $coach->name }}

                            @if ($coach->nip)
                                - NIP {{ $coach->nip }}
                            @endif
                        </option>

                    @endforeach

                </select>

                @error('responsibleCoachId')
                    <div class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </div>
                @enderror

            </div>

        </section>


        {{-- GUGUS DEPAN --}}

        <section
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-6
                   shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <h2 class="text-lg font-semibold">
                Identitas Gugus Depan
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
                        Nomor Gudep Putra
                    </label>

                    <input
                        type="text"
                        wire:model="gudepMaleNumber"
                        placeholder="Contoh: 03.061"
                        class="w-full rounded-lg
                               border border-zinc-300
                               bg-white px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-950"
                    >

                    @error('gudepMaleNumber')
                        <div class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </div>
                    @enderror
                </div>


                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Nomor Gudep Putri
                    </label>

                    <input
                        type="text"
                        wire:model="gudepFemaleNumber"
                        placeholder="Contoh: 03.062"
                        class="w-full rounded-lg
                               border border-zinc-300
                               bg-white px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-950"
                    >

                    @error('gudepFemaleNumber')
                        <div class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

            </div>

        </section>


        {{-- DOKUMEN --}}

        <section
            class="rounded-xl border
                   border-zinc-200
                   bg-white p-6
                   shadow-sm
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <h2 class="text-lg font-semibold">
                Informasi Dokumen
            </h2>


            <div class="mt-5 space-y-4">

                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Kota Penandatanganan
                    </label>

                    <input
                        type="text"
                        wire:model="signingCity"
                        placeholder="Contoh: Denpasar"
                        class="w-full rounded-lg
                               border border-zinc-300
                               bg-white px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-950"
                    >

                    @error('signingCity')
                        <div class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </div>
                    @enderror
                </div>


                <div>
                    <label
                        class="mb-1 block
                               text-sm font-medium"
                    >
                        Catatan Dokumen
                    </label>

                    <textarea
                        wire:model="documentNote"
                        rows="4"
                        class="w-full rounded-lg
                               border border-zinc-300
                               bg-white px-3 py-2
                               dark:border-zinc-700
                               dark:bg-zinc-950"
                    ></textarea>

                    @error('documentNote')
                        <div class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

            </div>

        </section>


        @can('school_documents.manage')

            <div class="flex justify-end">

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="rounded-lg
                           bg-zinc-900
                           px-5 py-2.5
                           text-sm font-medium
                           text-white
                           hover:bg-zinc-800
                           disabled:opacity-50
                           dark:bg-white
                           dark:text-zinc-900"
                >
                    <span
                        wire:loading.remove
                        wire:target="save"
                    >
                        Simpan Pengaturan
                    </span>

                    <span
                        wire:loading
                        wire:target="save"
                    >
                        Menyimpan...
                    </span>
                </button>

            </div>

        @endcan

    </form>

</div>