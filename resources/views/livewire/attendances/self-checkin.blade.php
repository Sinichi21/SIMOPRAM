<div
    class="space-y-6"
    x-data="{
        locating: false,
        gpsError: '',

        checkIn(sessionId) {
            this.gpsError = '';

            if (! navigator.geolocation) {
                this.gpsError =
                    'Perangkat tidak mendukung geolocation.';

                return;
            }

            this.locating = true;

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    try {
                        await $wire.checkIn(
                            sessionId,
                            position.coords.latitude,
                            position.coords.longitude,
                            position.coords.accuracy
                        );
                    } finally {
                        this.locating = false;
                    }
                },

                (error) => {
                    this.locating = false;

                    switch (error.code) {
                        case 1:
                            this.gpsError =
                                'Izin lokasi ditolak.';
                            break;

                        case 2:
                            this.gpsError =
                                'Lokasi tidak dapat diperoleh.';
                            break;

                        case 3:
                            this.gpsError =
                                'Permintaan lokasi terlalu lama.';
                            break;

                        default:
                            this.gpsError =
                                'Gagal memperoleh lokasi.';
                    }
                },

                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0,
                }
            );
        }
    }"
>

    <div>
        <h1 class="text-2xl font-semibold">
            Absensi Saya
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            {{ $student->name }}
        </p>
    </div>


    @if ($successMessage)

        <div
            class="rounded-lg border
                   border-green-200
                   bg-green-50 p-4
                   text-sm text-green-700"
        >
            {{ $successMessage }}
        </div>

    @endif


    @error('location')

        <div
            class="rounded-lg border
                   border-red-200
                   bg-red-50 p-4
                   text-sm text-red-700"
        >
            {{ $message }}
        </div>

    @enderror


    <div
        x-show="gpsError"
        x-cloak
        class="rounded-lg border
               border-red-200
               bg-red-50 p-4
               text-sm text-red-700"
    >
        <span x-text="gpsError"></span>
    </div>


    <div class="space-y-4">

        @forelse ($sessions as $session)

            @php
                $attendance =
                    $session
                        ->attendances
                        ->first();
            @endphp

            <div
                class="rounded-xl border
                       border-zinc-200
                       bg-white p-6
                       shadow-sm
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

                        <h2 class="text-lg font-semibold">
                            {{ $session->activity->title }}
                        </h2>

                        <div
                            class="mt-1 text-sm
                                   text-zinc-500"
                        >
                            {{ $session->name }}
                        </div>

                        <div
                            class="mt-2 text-sm
                                   text-zinc-500"
                        >
                            Dibuka:
                            {{ $session->open_at
                                ->format('H:i')
                            }}

                            ·

                            Ditutup:
                            {{ $session->close_at
                                ->format('H:i')
                            }}
                        </div>

                        <div
                            class="mt-1 text-sm
                                   text-zinc-500"
                        >
                            Radius:
                            {{ $session->radius_m }}
                            meter
                        </div>

                    </div>


                    @if ($attendance)

                        <div
                            class="rounded-lg
                                   bg-green-100
                                   px-4 py-2
                                   text-sm font-semibold
                                   text-green-700"
                        >
                            Sudah Absen:
                            {{ ucfirst(
                                $attendance->status
                            ) }}
                        </div>

                    @else

                        <button
                            type="button"
                            x-on:click="
                                checkIn(
                                    {{ $session->id }}
                                )
                            "
                            x-bind:disabled="locating"
                            class="rounded-lg
                                   bg-zinc-900
                                   px-4 py-2
                                   text-sm font-semibold
                                   text-white
                                   disabled:opacity-50
                                   dark:bg-white
                                   dark:text-zinc-900"
                        >
                            <span
                                x-show="! locating"
                            >
                                Ambil Lokasi & Absen
                            </span>

                            <span
                                x-show="locating"
                            >
                                Mencari lokasi...
                            </span>
                        </button>

                    @endif

                </div>

            </div>

        @empty

            <div
                class="rounded-xl border
                       border-zinc-200
                       bg-white p-8
                       text-center
                       text-zinc-500
                       dark:border-zinc-800
                       dark:bg-zinc-900"
            >
                Tidak ada sesi absensi
                yang sedang dibuka.
            </div>

        @endforelse

    </div>

</div>