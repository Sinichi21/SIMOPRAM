<div class="mx-auto max-w-7xl space-y-6">

    <div>
        <h1
            class="text-2xl font-bold
                   tracking-tight text-zinc-900
                   dark:text-white"
        >
            Profil Pramuka Saya
        </h1>

        <p
            class="mt-1 text-sm text-zinc-500
                   dark:text-zinc-400"
        >
            Informasi keanggotaan dan data
            Pramuka Anda.
        </p>
    </div>


    @if (! $student)

        <div
            class="rounded-2xl border
                   border-amber-200
                   bg-amber-50 p-6
                   dark:border-amber-900/50
                   dark:bg-amber-950/30"
        >
            <div class="flex gap-4">

                <flux:icon.exclamation-triangle
                    class="size-6
                           text-amber-600"
                />

                <div>
                    <div class="font-semibold">
                        Data siswa belum terhubung
                    </div>

                    <p
                        class="mt-1 text-sm
                               text-zinc-600
                               dark:text-zinc-400"
                    >
                        Hubungi administrator sekolah
                        untuk menghubungkan akun dengan
                        data siswa.
                    </p>
                </div>

            </div>
        </div>

    @else

        {{-- =================================================
        IDENTITAS SISWA
        ================================================== --}}

        <section
            class="overflow-hidden rounded-2xl
                   border border-zinc-200
                   bg-white
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="bg-gradient-to-br
                       from-emerald-700
                       via-emerald-600
                       to-teal-600
                       px-6 py-7 text-white"
            >

                <div
                    class="flex flex-col gap-5
                           sm:flex-row
                           sm:items-center"
                >

                    <div
                        class="flex size-16
                               shrink-0 items-center
                               justify-center
                               rounded-2xl
                               bg-white/15
                               text-2xl font-bold"
                    >
                        {{
                            mb_strtoupper(
                                mb_substr(
                                    $student->name,
                                    0,
                                    1
                                )
                            )
                        }}
                    </div>

                    <div>

                        <p
                            class="text-sm
                                   text-emerald-100"
                        >
                            Anggota Pramuka
                        </p>

                        <h2
                            class="text-2xl
                                   font-bold"
                        >
                            {{ $student->name }}
                        </h2>

                        <div
                            class="mt-2 flex
                                   flex-wrap gap-x-4
                                   gap-y-1 text-sm
                                   text-emerald-50"
                        >

                            @if ($student->nis)
                                <span>
                                    NIS
                                    {{ $student->nis }}
                                </span>
                            @endif

                            @if (
                                $enrollment
                                    ?->classroom
                            )
                                <span>
                                    Kelas
                                    {{
                                        $enrollment
                                            ->classroom
                                            ->name
                                    }}
                                </span>
                            @endif

                        </div>

                    </div>

                </div>

            </div>


            <div
                class="grid gap-6 p-6
                       md:grid-cols-2
                       xl:grid-cols-4"
            >

                <div>
                    <div
                        class="text-xs font-medium
                               uppercase tracking-wide
                               text-zinc-400"
                    >
                        Pangkalan
                    </div>

                    <div
                        class="mt-1 font-medium
                               text-zinc-900
                               dark:text-white"
                    >
                        {{
                            $student
                                ->school
                                ?->name
                            ?? '-'
                        }}
                    </div>
                </div>


                <div>
                    <div
                        class="text-xs font-medium
                               uppercase tracking-wide
                               text-zinc-400"
                    >
                        Golongan
                    </div>

                    <div
                        class="mt-1 font-medium
                               text-zinc-900
                               dark:text-white"
                    >
                        {{
                            $scoutLevel?->name
                            ?? 'Belum ditentukan'
                        }}
                    </div>
                </div>


                <div>
                    <div
                        class="text-xs font-medium
                               uppercase tracking-wide
                               text-zinc-400"
                    >
                        Bergabung
                    </div>

                    <div
                        class="mt-1 font-medium
                               text-zinc-900
                               dark:text-white"
                    >
                        {{
                            $student->joined_at
                                ?->locale('id')
                                ->translatedFormat(
                                    'd F Y'
                                )
                            ?? '-'
                        }}
                    </div>
                </div>


                <div>
                    <div
                        class="text-xs font-medium
                               uppercase tracking-wide
                               text-zinc-400"
                    >
                        Status
                    </div>

                    <div class="mt-1">

                        <span
                            class="inline-flex
                                   rounded-full
                                   bg-emerald-50
                                   px-2.5 py-1
                                   text-xs
                                   font-medium
                                   text-emerald-700
                                   dark:bg-emerald-950/50
                                   dark:text-emerald-300"
                        >
                            {{
                                ucfirst(
                                    $student->status
                                )
                            }}
                        </span>

                    </div>
                </div>

            </div>

        </section>


        {{-- =================================================
        REGU / BARUNG
        ================================================== --}}

        <section
            id="regu"
            class="rounded-2xl border
                   border-zinc-200
                   bg-white
                   dark:border-zinc-800
                   dark:bg-zinc-900"
        >

            <div
                class="flex items-center
                       justify-between
                       border-b
                       border-zinc-100
                       px-6 py-5
                       dark:border-zinc-800"
            >

                <div>

                    <h2
                        class="font-semibold
                               text-zinc-900
                               dark:text-white"
                    >
                        {{ $unitType }} Saya
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-zinc-500"
                    >
                        Kelompok Pramuka
                        yang sedang Anda ikuti.
                    </p>

                </div>

                <div
                    class="flex size-10
                           items-center
                           justify-center
                           rounded-xl
                           bg-emerald-50
                           text-emerald-600
                           dark:bg-emerald-950/50
                           dark:text-emerald-400"
                >
                    <flux:icon.user-group
                        class="size-5"
                    />
                </div>

            </div>


            @if ($scoutUnit)

                <div class="p-6">

                    <div
                        class="grid gap-5
                               sm:grid-cols-2"
                    >

                        <div
                            class="rounded-xl
                                   bg-zinc-50
                                   p-5
                                   dark:bg-zinc-800/60"
                        >
                            <div
                                class="text-xs
                                       font-medium
                                       uppercase
                                       tracking-wide
                                       text-zinc-400"
                            >
                                Nama
                                {{ $unitType }}
                            </div>

                            <div
                                class="mt-2 text-xl
                                       font-bold
                                       text-zinc-900
                                       dark:text-white"
                            >
                                {{ $unitName }}
                            </div>
                        </div>


                        <div
                            class="rounded-xl
                                   bg-zinc-50
                                   p-5
                                   dark:bg-zinc-800/60"
                        >
                            <div
                                class="text-xs
                                       font-medium
                                       uppercase
                                       tracking-wide
                                       text-zinc-400"
                            >
                                Jabatan Saya
                            </div>

                            <div
                                class="mt-2 text-xl
                                       font-bold
                                       text-zinc-900
                                       dark:text-white"
                            >
                                {{ $memberRole }}
                            </div>
                        </div>

                    </div>


                    {{-- Anggota --}}
                    <div class="mt-7">

                        <h3
                            class="font-semibold
                                   text-zinc-900
                                   dark:text-white"
                        >
                            Anggota
                            {{ $unitType }}
                        </h3>

                        <div
                            class="mt-3 divide-y
                                   divide-zinc-100
                                   overflow-hidden
                                   rounded-xl border
                                   border-zinc-200
                                   dark:divide-zinc-800
                                   dark:border-zinc-700"
                        >

                            @forelse (
                                $members as $member
                            )

                                <div
                                    class="flex
                                           items-center
                                           justify-between
                                           gap-4 px-4
                                           py-3.5"
                                >

                                    <div
                                        class="flex
                                               items-center
                                               gap-3"
                                    >

                                        <div
                                            class="flex
                                                   size-9
                                                   items-center
                                                   justify-center
                                                   rounded-full
                                                   bg-zinc-100
                                                   text-sm
                                                   font-semibold
                                                   text-zinc-600
                                                   dark:bg-zinc-800
                                                   dark:text-zinc-300"
                                        >
                                            {{
                                                mb_strtoupper(
                                                    mb_substr(
                                                        $member[
                                                            'student'
                                                        ]->name,
                                                        0,
                                                        1
                                                    )
                                                )
                                            }}
                                        </div>

                                        <div>

                                            <div
                                                class="text-sm
                                                       font-medium
                                                       text-zinc-900
                                                       dark:text-white"
                                            >
                                                {{
                                                    $member[
                                                        'student'
                                                    ]->name
                                                }}

                                                @if (
                                                    $member[
                                                        'isCurrentUser'
                                                    ]
                                                )
                                                    <span
                                                        class="ml-1
                                                               text-xs
                                                               text-emerald-600"
                                                    >
                                                        Anda
                                                    </span>
                                                @endif
                                            </div>

                                            <div
                                                class="text-xs
                                                       text-zinc-500"
                                            >
                                                NIS
                                                {{
                                                    $member[
                                                        'student'
                                                    ]->nis
                                                    ?: '-'
                                                }}
                                            </div>

                                        </div>

                                    </div>

                                    <span
                                        class="rounded-full
                                               bg-zinc-100
                                               px-2.5 py-1
                                               text-xs
                                               font-medium
                                               text-zinc-600
                                               dark:bg-zinc-800
                                               dark:text-zinc-300"
                                    >
                                        {{
                                            $member[
                                                'role'
                                            ]
                                        }}
                                    </span>

                                </div>

                            @empty

                                <div
                                    class="px-4 py-8
                                           text-center
                                           text-sm
                                           text-zinc-500"
                                >
                                    Belum ada anggota
                                    yang tercatat.
                                </div>

                            @endforelse

                        </div>

                    </div>

                </div>

            @else

                <div
                    class="px-6 py-12
                           text-center"
                >
                    <div
                        class="mx-auto flex
                               size-12
                               items-center
                               justify-center
                               rounded-full
                               bg-zinc-100
                               text-zinc-400
                               dark:bg-zinc-800"
                    >
                        <flux:icon.user-group
                            class="size-5"
                        />
                    </div>

                    <h3
                        class="mt-3 font-medium
                               text-zinc-900
                               dark:text-white"
                    >
                        Belum tergabung dalam
                        {{ $unitType }}
                    </h3>

                    <p
                        class="mt-1 text-sm
                               text-zinc-500"
                    >
                        Pembina atau administrator
                        sekolah dapat menempatkan Anda
                        pada Regu/Barung.
                    </p>

                </div>

            @endif

        </section>

    @endif

</div>