<div class="space-y-6">

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
            Akun Siswa
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            {{ $student->name }}
            · NIS {{ $student->nis }}
        </p>
    </div>


    @if (! $student->user)

        <form
            wire:submit="createAccount"
            class="max-w-xl rounded-xl border
                   border-zinc-200 bg-white p-6
                   dark:border-zinc-800 dark:bg-zinc-900"
        >

            <h2 class="text-lg font-semibold">
                Buat Akun Login
            </h2>

            <p class="mt-1 text-sm text-zinc-500">
                Akun ini digunakan siswa untuk
                mengakses SIMPRAM dan absensi mandiri.
            </p>


            <div class="mt-5">

                <label class="mb-1 block text-sm font-medium">
                    Email
                </label>

                <input
                    wire:model="email"
                    type="email"
                    class="w-full rounded-lg border
                           border-zinc-300 px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-800"
                >

                @error('email')
                    <p class="mt-1 text-sm text-red-500">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            <div class="mt-4">

                <label class="mb-1 block text-sm font-medium">
                    Password
                </label>

                <input
                    wire:model="password"
                    type="password"
                    class="w-full rounded-lg border
                           border-zinc-300 px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-800"
                >

            </div>


            <div class="mt-4">

                <label class="mb-1 block text-sm font-medium">
                    Konfirmasi Password
                </label>

                <input
                    wire:model="password_confirmation"
                    type="password"
                    class="w-full rounded-lg border
                           border-zinc-300 px-3 py-2
                           dark:border-zinc-700
                           dark:bg-zinc-800"
                >

                @error('password')
                    <p class="mt-1 text-sm text-red-500">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            <button
                type="submit"
                class="mt-6 rounded-lg bg-zinc-900
                       px-4 py-2 text-sm font-medium
                       text-white dark:bg-white
                       dark:text-zinc-900"
            >
                Buat Akun
            </button>

        </form>

    @else

        <div
            class="max-w-xl rounded-xl border
                   border-zinc-200 bg-white p-6
                   dark:border-zinc-800 dark:bg-zinc-900"
        >

            <div class="text-sm text-zinc-500">
                Akun terhubung
            </div>

            <div class="mt-1 font-semibold">
                {{ $student->user->email }}
            </div>


            <form
                wire:submit="resetPassword"
                class="mt-6"
            >

                <h3 class="font-semibold">
                    Ubah Password
                </h3>

                <input
                    wire:model="password"
                    type="password"
                    placeholder="Password baru"
                    class="mt-3 w-full rounded-lg
                           border border-zinc-300
                           px-3 py-2"
                >

                <input
                    wire:model="password_confirmation"
                    type="password"
                    placeholder="Konfirmasi password"
                    class="mt-3 w-full rounded-lg
                           border border-zinc-300
                           px-3 py-2"
                >

                <button
                    type="submit"
                    class="mt-4 rounded-lg
                           bg-zinc-900 px-4 py-2
                           text-sm text-white"
                >
                    Ubah Password
                </button>

            </form>

        </div>

    @endif

</div>
