<div class="mx-auto max-w-7xl space-y-6">

    <div>
        <h1
            class="text-2xl font-bold
                   tracking-tight
                   text-zinc-900
                   dark:text-white"
        >
            Riwayat Nilai
        </h1>

        <p
            class="mt-1 text-sm
                   text-zinc-500"
        >
            Perkembangan hasil penilaian
            Pramuka dari semester ke semester.
        </p>
    </div>


    @if (! $student)

        <div
            class="rounded-2xl
                   border border-amber-200
                   bg-amber-50 p-6
                   dark:border-amber-900/50
                   dark:bg-amber-950/30"
        >
            Data siswa belum terhubung.
        </div>

    @else

        <div class="space-y-4">

            @forelse (
                $histories as $history
            )

                <article
                    class="overflow-hidden
                           rounded-2xl
                           border border-zinc-200
                           bg-white
                           dark:border-zinc-800
                           dark:bg-zinc-900"
                >

                    <div
                        class="flex flex-col
                               justify-between
                               gap-4
                               border-b
                               border-zinc-100
                               px-5 py-4
                               sm:flex-row
                               sm:items-center
                               dark:border-zinc-800"
                    >

                        <div>

                            <div
                                class="font-semibold
                                       text-zinc-900
                                       dark:text-white"
                            >
                                {{
                                    $history[
                                        'semester'
                                    ]->name
                                }}
                            </div>

                            <div
                                class="mt-1 text-sm
                                       text-zinc-500"
                            >
                                {{
                                    $history[
                                        'academicYear'
                                    ]?->name
                                    ?? '-'
                                }}
                            </div>

                        </div>


                        <div
                            class="flex items-center
                                   gap-3"
                        >

                            @if (
                                $history[
                                    'isOfficial'
                                ]
                            )
                                <span
                                    class="rounded-full
                                           bg-emerald-50
                                           px-2.5 py-1
                                           text-xs
                                           font-medium
                                           text-emerald-700
                                           dark:bg-emerald-950/50
                                           dark:text-emerald-300"
                                >
                                    Resmi

                                    @if (
                                        $history[
                                            'closureVersion'
                                        ]
                                    )
                                        v{{
                                            $history[
                                                'closureVersion'
                                            ]
                                        }}
                                    @endif
                                </span>
                            @else
                                <span
                                    class="rounded-full
                                           bg-amber-50
                                           px-2.5 py-1
                                           text-xs
                                           font-medium
                                           text-amber-700
                                           dark:bg-amber-950/50
                                           dark:text-amber-300"
                                >
                                    Berjalan
                                </span>
                            @endif


                            <div
                                class="text-right"
                            >

                                <div
                                    class="text-2xl
                                           font-bold
                                           text-zinc-900
                                           dark:text-white"
                                >
                                    @if (
                                        $history[
                                            'finalScore'
                                        ]
                                        !== null
                                    )
                                        {{
                                            number_format(
                                                (float)
                                                $history[
                                                    'finalScore'
                                                ],
                                                2,
                                                ',',
                                                '.'
                                            )
                                        }}
                                    @else
                                        -
                                    @endif
                                </div>

                                @if (
                                    $history[
                                        'letterGrade'
                                    ]
                                )
                                    <div
                                        class="text-xs
                                               font-semibold
                                               text-emerald-600"
                                    >
                                        Predikat
                                        {{
                                            $history[
                                                'letterGrade'
                                            ]
                                        }}
                                    </div>
                                @endif

                            </div>

                        </div>

                    </div>


                    <div class="p-5">

                        <div
                            class="grid gap-3
                                   sm:grid-cols-2
                                   lg:grid-cols-3"
                        >

                            @foreach (
                                $history[
                                    'factors'
                                ]
                                as $factor
                            )

                                <div
                                    class="rounded-xl
                                           bg-zinc-50
                                           p-4
                                           dark:bg-zinc-800/60"
                                >

                                    <div
                                        class="flex
                                               items-start
                                               justify-between
                                               gap-3"
                                    >

                                        <div>

                                            <div
                                                class="text-sm
                                                       font-medium
                                                       text-zinc-900
                                                       dark:text-white"
                                            >
                                                {{
                                                    $factor[
                                                        'name'
                                                    ]
                                                }}
                                            </div>

                                            <div
                                                class="mt-1
                                                       text-xs
                                                       text-zinc-500"
                                            >
                                                Bobot
                                                {{
                                                    number_format(
                                                        $factor[
                                                            'weight'
                                                        ],
                                                        2,
                                                        ',',
                                                        '.'
                                                    )
                                                }}%
                                            </div>

                                        </div>

                                        <div
                                            class="text-lg
                                                   font-bold
                                                   text-emerald-600"
                                        >
                                            @if (
                                                $factor[
                                                    'score'
                                                ]
                                                !== null
                                            )
                                                {{
                                                    number_format(
                                                        (float)
                                                        $factor[
                                                            'score'
                                                        ],
                                                        0
                                                    )
                                                }}
                                            @else
                                                -
                                            @endif
                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        </div>


                        @if (
                            filled(
                                $history[
                                    'description'
                                ]
                            )
                        )

                            <div
                                class="mt-5
                                       rounded-xl
                                       border
                                       border-zinc-200
                                       p-4
                                       dark:border-zinc-700"
                            >
                                <div
                                    class="text-xs
                                           font-semibold
                                           uppercase
                                           tracking-wide
                                           text-zinc-400"
                                >
                                    Deskripsi
                                </div>

                                <p
                                    class="mt-2 text-sm
                                           leading-6
                                           text-zinc-600
                                           dark:text-zinc-300"
                                >
                                    {{
                                        $history[
                                            'description'
                                        ]
                                    }}
                                </p>
                            </div>

                        @endif

                    </div>

                </article>

            @empty

                <div
                    class="rounded-2xl
                           border border-zinc-200
                           bg-white px-6
                           py-12 text-center
                           dark:border-zinc-800
                           dark:bg-zinc-900"
                >

                    <flux:icon.academic-cap
                        class="mx-auto size-10
                               text-zinc-300"
                    />

                    <h2
                        class="mt-4 font-semibold
                               text-zinc-900
                               dark:text-white"
                    >
                        Belum ada riwayat nilai
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-zinc-500"
                    >
                        Riwayat akan muncul setelah
                        siswa memiliki data penilaian.
                    </p>

                </div>

            @endforelse

        </div>

    @endif

</div>