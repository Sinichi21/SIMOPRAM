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
            Pengaturan Notifikasi
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            Hubungkan akun untuk menerima
            notifikasi SIMPRAM.
        </p>

    </div>


    <form wire:submit="savePreferences" class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading>WhatsApp dan Email</flux:heading>
        <flux:heading>Email & WhatsApp saya</flux:heading>
        <flux:text>Email penerima: {{ auth()->user()->email }}</flux:text>
        <flux:input wire:model="whatsappNumber" label="Nomor WhatsApp saya" placeholder="081234567890" />
        <flux:switch wire:model="whatsappEnabled" label="Saya ingin menerima pengumuman melalui WhatsApp" />
        <flux:switch wire:model="emailEnabled" label="Saya ingin menerima pengumuman melalui email akun" />
        <flux:text>Pilihan pengumuman sekolah ini berlaku untuk sekolah aktif. Notifikasi agenda/lomba dikirim melalui kontak dan kanal yang Anda pilih saat pendaftaran kegiatan, termasuk bila menggunakan tautan sementara.</flux:text>
        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">Simpan pilihan notifikasi</flux:button>
    </form>

    {{-- TELEGRAM --}}

    <div
        class="rounded-xl border
               border-zinc-200 bg-white
               p-6 shadow-sm
               dark:border-zinc-800
               dark:bg-zinc-900"
    >

        <div
            class="flex flex-col gap-4
                   md:flex-row
                   md:items-center
                   md:justify-between"
        >

            <div>

                <div
                    class="flex items-center
                           gap-2"
                >

                    <h2
                        class="text-lg
                               font-semibold"
                    >
                        Telegram
                    </h2>


                    @if (
                        $telegramChannel
                        &&
                        $telegramChannel
                            ->is_verified
                        &&
                        $telegramChannel
                            ->is_active
                    )

                        <span
                            class="rounded-full
                                   bg-green-100
                                   px-2 py-1
                                   text-xs font-medium
                                   text-green-700"
                        >
                            Terhubung
                        </span>

                    @else

                        <span
                            class="rounded-full
                                   bg-zinc-100
                                   px-2 py-1
                                   text-xs
                                   text-zinc-600"
                        >
                            Belum Terhubung
                        </span>

                    @endif

                </div>


                <p
                    class="mt-2 text-sm
                           text-zinc-500"
                >
                    Terima pengumuman Pramuka
                    langsung melalui Telegram.
                </p>


                @if (
                    $telegramChannel
                    &&
                    $telegramChannel
                        ->is_verified
                )

                    @php
                        $username =
                            data_get(
                                $telegramChannel
                                    ->metadata,
                                'username'
                            );
                    @endphp


                    @if ($username)

                        <p
                            class="mt-2 text-sm
                                   font-medium"
                        >
                            Telegram:
                            {{ '@' . $username }}
                        </p>

                    @endif

                @endif

            </div>


            <div>

                @if (
                    $telegramChannel
                    &&
                    $telegramChannel
                        ->is_verified
                    &&
                    $telegramChannel
                        ->is_active
                )

                    <button
                        type="button"
                        wire:click="
                            disconnectTelegram
                        "
                        wire:confirm="
                            Nonaktifkan Telegram?
                        "
                        class="rounded-lg
                               border
                               border-red-300
                               px-4 py-2
                               text-sm
                               text-red-600"
                    >
                        Putuskan Telegram
                    </button>

                @else

                    <button
                        type="button"
                        wire:click="
                            connectTelegram
                        "
                        class="rounded-lg
                               bg-zinc-900
                               px-4 py-2
                               text-sm font-medium
                               text-white
                               dark:bg-white
                               dark:text-zinc-900"
                    >
                        Hubungkan Telegram
                    </button>

                @endif

            </div>

        </div>


        {{-- LINK SEKALI PAKAI --}}

        @if ($telegramLink)

            <div
                class="mt-6 rounded-lg
                       border border-blue-200
                       bg-blue-50 p-4
                       dark:border-blue-900
                       dark:bg-blue-950"
            >

                <div
                    class="font-medium
                           text-blue-900
                           dark:text-blue-200"
                >
                    Link Telegram siap
                </div>


                <p
                    class="mt-1 text-sm
                           text-blue-700
                           dark:text-blue-300"
                >
                    Link berlaku selama
                    10 menit dan hanya dapat
                    digunakan satu kali.
                </p>


                <a
                    href="{{ $telegramLink }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-4 inline-flex
                           rounded-lg
                           bg-blue-600
                           px-4 py-2
                           text-sm font-semibold
                           text-white"
                >
                    Buka Telegram
                </a>

            </div>

        @endif

    </div>




</div>
