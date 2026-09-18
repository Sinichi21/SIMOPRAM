<div class="mx-auto max-w-7xl space-y-6">

    {{-- =====================================================
    HEADER
    ====================================================== --}}

    <div
        class="flex flex-col justify-between
               gap-4 lg:flex-row
               lg:items-end"
    >

        <div>

            <h1
                class="text-2xl font-bold
                       tracking-tight
                       text-zinc-900
                       dark:text-white"
            >
                Keterampilan Saya
            </h1>

            <p
                class="mt-1 text-sm
                       text-zinc-500
                       dark:text-zinc-400"
            >
                Hasil penilaian keterampilan
                berdasarkan kegiatan Pramuka
                yang telah dipublikasikan.
            </p>

        </div>


        @if ($student)

            <div
                class="grid gap-2
                       sm:grid-cols-2"
            >

                {{-- Tahun Ajaran --}}
                <select
                    wire:model.live="
                        academicYearId
                    "
                    class="rounded-lg
                           border-zinc-300
                           bg-white
                           text-sm
                           text-zinc-800
                           shadow-sm
                           focus:border-emerald-500
                           focus:ring-emerald-500
                           dark:border-zinc-700
                           dark:bg-zinc-900
                           dark:text-zinc-200"
                >
                    @foreach (
                        $academicYears
                        as $academicYear
                    )
                        <option
                            value="{{
                                $academicYear->id
                            }}"
                        >
                            {{
                                $academicYear->name
                            }}
                        </option>
                    @endforeach
                </select>


                {{-- Semester --}}
                <select
                    wire:model.live="
                        semesterId
                    "
                    class="rounded-lg
                           border-zinc-300
                           bg-white
                           text-sm
                           text-zinc-800
                           shadow-sm
                           focus:border-emerald-500
                           focus:ring-emerald-500
                           dark:border-zinc-700
                           dark:bg-zinc-900
                           dark:text-zinc-200"
                >
                    @foreach (
                        $semesters
                        as $semester
                    )
                        <option
                            value="{{
                                $semester->id
                            }}"
                        >
                            {{
                                $semester->name
                            }}
                        </option>
                    @endforeach
                </select>

            </div>

        @endif

    </div>


    @if (! $student)

        {{-- =================================================
        BELUM TERHUBUNG
        ================================================== --}}

        <div
            class="rounded-2xl
                   border border-amber-200
                   bg-amber-50 p-6
                   dark:border-amber-900/50
                   dark:bg-amber-950/30"
        >

            <div class="flex gap-4">

                <flux:icon.exclamation-triangle
                    class="size-6
                           shrink-0
                           text-amber-600"
                />

                <div>

                    <div
                        class="font-semibold
                               text-zinc-900
                               dark:text-white"
                    >
                        Data siswa belum terhubung
                    </div>

                    <p
                        class="mt-1 text-sm
                               text-zinc-600
                               dark:text-zinc-400"
                    >
                        Hubungi administrator sekolah
                        agar akun Anda dihubungkan
                        dengan data siswa.
                    </p>

                </div>

            </div>

        </div>

    @else

        {{-- =================================================
        SUMMARY
        ================================================== --}}

        <section
            class="grid grid-cols-2
                   gap-3 lg:grid-cols-4"
        >

            {{-- Total --}}
            <div
                class="rounded-2xl
                       border border-zinc-200
                       bg-white p-5
                       dark:border-zinc-800
                       dark:bg-zinc-900"
            >

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
                    <flux:icon.clipboard-document-check
                        class="size-5"
                    />
                </div>

                <div
                    class="mt-4 text-2xl
                           font-bold
                           text-zinc-900
                           dark:text-white"
                >
                    {{ $statistics['total'] }}
                </div>

                <div
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Penilaian
                </div>

            </div>


            {{-- Individual --}}
            <div
                class="rounded-2xl
                       border border-zinc-200
                       bg-white p-5
                       dark:border-zinc-800
                       dark:bg-zinc-900"
            >

                <div
                    class="flex size-10
                           items-center
                           justify-center
                           rounded-xl
                           bg-blue-50
                           text-blue-600
                           dark:bg-blue-950/50
                           dark:text-blue-400"
                >
                    <flux:icon.user
                        class="size-5"
                    />
                </div>

                <div
                    class="mt-4 text-2xl
                           font-bold
                           text-zinc-900
                           dark:text-white"
                >
                    {{
                        $statistics[
                            'individual'
                        ]
                    }}
                </div>

                <div
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Individu
                </div>

            </div>


            {{-- Regu --}}
            <div
                class="rounded-2xl
                       border border-zinc-200
                       bg-white p-5
                       dark:border-zinc-800
                       dark:bg-zinc-900"
            >

                <div
                    class="flex size-10
                           items-center
                           justify-center
                           rounded-xl
                           bg-violet-50
                           text-violet-600
                           dark:bg-violet-950/50
                           dark:text-violet-400"
                >
                    <flux:icon.user-group
                        class="size-5"
                    />
                </div>

                <div
                    class="mt-4 text-2xl
                           font-bold
                           text-zinc-900
                           dark:text-white"
                >
                    {{ $statistics['group'] }}
                </div>

                <div
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Regu / Barung
                </div>

            </div>


            {{-- Rata-rata --}}
            <div
                class="rounded-2xl
                       border border-zinc-200
                       bg-white p-5
                       dark:border-zinc-800
                       dark:bg-zinc-900"
            >

                <div
                    class="flex size-10
                           items-center
                           justify-center
                           rounded-xl
                           bg-amber-50
                           text-amber-600
                           dark:bg-amber-950/50
                           dark:text-amber-400"
                >
                    <flux:icon.chart-bar
                        class="size-5"
                    />
                </div>

                <div
                    class="mt-4 text-2xl
                           font-bold
                           text-zinc-900
                           dark:text-white"
                >
                    @if (
                        $statistics[
                            'average'
                        ] !== null
                    )
                        {{
                            number_format(
                                $statistics[
                                    'average'
                                ],
                                1,
                                ',',
                                '.'
                            )
                        }}
                    @else
                        -
                    @endif
                </div>

                <div
                    class="mt-1 text-sm
                           text-zinc-500"
                >
                    Rata-rata
                </div>

            </div>

        </section>


        {{-- =================================================
        PENILAIAN
        ================================================== --}}

        <section class="space-y-4">

            @forelse (
                $assessments
                as $assessment
            )

                <article
                    class="overflow-hidden
                           rounded-2xl
                           border border-zinc-200
                           bg-white
                           dark:border-zinc-800
                           dark:bg-zinc-900"
                >

                    {{-- HEADER CARD --}}
                    <div
                        class="border-b
                               border-zinc-100
                               px-5 py-5
                               dark:border-zinc-800"
                    >

                        <div
                            class="flex flex-col
                                   justify-between
                                   gap-5
                                   md:flex-row
                                   md:items-start"
                        >

                            <div
                                class="min-w-0"
                            >

                                <div
                                    class="flex
                                           flex-wrap
                                           items-center
                                           gap-2"
                                >

                                    @if (
                                        $assessment[
                                            'isGroup'
                                        ]
                                    )

                                        <span
                                            class="inline-flex
                                                   items-center
                                                   gap-1.5
                                                   rounded-full
                                                   bg-violet-50
                                                   px-2.5
                                                   py-1
                                                   text-xs
                                                   font-medium
                                                   text-violet-700
                                                   dark:bg-violet-950/50
                                                   dark:text-violet-300"
                                        >
                                            <flux:icon.user-group
                                                class="size-3.5"
                                            />

                                            Regu / Barung
                                        </span>

                                    @else

                                        <span
                                            class="inline-flex
                                                   items-center
                                                   gap-1.5
                                                   rounded-full
                                                   bg-blue-50
                                                   px-2.5
                                                   py-1
                                                   text-xs
                                                   font-medium
                                                   text-blue-700
                                                   dark:bg-blue-950/50
                                                   dark:text-blue-300"
                                        >
                                            <flux:icon.user
                                                class="size-3.5"
                                            />

                                            Individu
                                        </span>

                                    @endif


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
                                            $assessment[
                                                'factorName'
                                            ]
                                        }}
                                    </span>

                                </div>


                                <h2
                                    class="mt-3
                                           text-lg
                                           font-semibold
                                           text-zinc-900
                                           dark:text-white"
                                >
                                    {{
                                        $assessment[
                                            'title'
                                        ]
                                    }}
                                </h2>


                                <p
                                    class="mt-1
                                           text-sm
                                           text-zinc-500"
                                >
                                    {{
                                        $assessment[
                                            'activityTitle'
                                        ]
                                    }}

                                    @if (
                                        $assessment[
                                            'activityStartAt'
                                        ]
                                    )
                                        ·

                                        {{
                                            \Illuminate\Support\Carbon::parse(
                                                $assessment[
                                                    'activityStartAt'
                                                ]
                                            )
                                                ->locale(
                                                    'id'
                                                )
                                                ->translatedFormat(
                                                    'd F Y'
                                                )
                                        }}
                                    @endif
                                </p>


                                @if (
                                    filled(
                                        $assessment[
                                            'description'
                                        ]
                                    )
                                )
                                    <p
                                        class="mt-3
                                               max-w-3xl
                                               text-sm
                                               leading-6
                                               text-zinc-600
                                               dark:text-zinc-400"
                                    >
                                        {{
                                            $assessment[
                                                'description'
                                            ]
                                        }}
                                    </p>
                                @endif

                            </div>


                            {{-- NILAI --}}
                            <div
                                class="shrink-0
                                       md:text-right"
                            >

                                <div
                                    class="text-xs
                                           font-medium
                                           uppercase
                                           tracking-wide
                                           text-zinc-400"
                                >
                                    Nilai
                                </div>

                                <div
                                    class="mt-1
                                           text-4xl
                                           font-bold
                                           text-emerald-600
                                           dark:text-emerald-400"
                                >
                                    {{
                                        number_format(
                                            $assessment[
                                                'normalized_score'
                                            ],
                                            1,
                                            ',',
                                            '.'
                                        )
                                    }}
                                </div>

                                <div
                                    class="mt-1
                                           text-xs
                                           text-zinc-500"
                                >
                                    Skala 0–100
                                </div>

                            </div>

                        </div>


                        {{-- PROGRESS --}}
                        <div
                            class="mt-5 h-2
                                   overflow-hidden
                                   rounded-full
                                   bg-zinc-100
                                   dark:bg-zinc-800"
                        >

                            <div
                                class="h-full
                                       rounded-full
                                       bg-emerald-600"
                                style="
                                    width:
                                    {{
                                        min(
                                            100,
                                            max(
                                                0,
                                                $assessment[
                                                    'normalized_score'
                                                ]
                                            )
                                        )
                                    }}%
                                "
                            ></div>

                        </div>

                    </div>


                    {{-- KRITERIA --}}
                    <div class="p-5">

                        <h3
                            class="text-sm
                                   font-semibold
                                   text-zinc-900
                                   dark:text-white"
                        >
                            Rincian Kriteria
                        </h3>


                        <div
                            class="mt-4
                                   grid gap-3
                                   md:grid-cols-2"
                        >

                            @forelse (
                                $assessment[
                                    'criteria'
                                ]
                                as $criterion
                            )

                                <div
                                    class="rounded-xl
                                           border
                                           border-zinc-200
                                           p-4
                                           dark:border-zinc-700"
                                >

                                    <div
                                        class="flex
                                               items-start
                                               justify-between
                                               gap-4"
                                    >

                                        <div>

                                            <div
                                                class="text-sm
                                                       font-medium
                                                       text-zinc-900
                                                       dark:text-white"
                                            >
                                                {{
                                                    $criterion[
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
                                                        $criterion[
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
                                            class="text-right"
                                        >

                                            <div
                                                class="text-lg
                                                       font-bold
                                                       text-zinc-900
                                                       dark:text-white"
                                            >
                                                {{
                                                    number_format(
                                                        $criterion[
                                                            'score'
                                                        ],
                                                        1,
                                                        ',',
                                                        '.'
                                                    )
                                                }}

                                                <span
                                                    class="text-sm
                                                           font-normal
                                                           text-zinc-400"
                                                >
                                                    /
                                                    {{
                                                        number_format(
                                                            $criterion[
                                                                'maxScore'
                                                            ],
                                                            0
                                                        )
                                                    }}
                                                </span>
                                            </div>

                                        </div>

                                    </div>


                                    <div
                                        class="mt-3 h-1.5
                                               overflow-hidden
                                               rounded-full
                                               bg-zinc-100
                                               dark:bg-zinc-800"
                                    >

                                        <div
                                            class="h-full
                                                   rounded-full
                                                   bg-emerald-600"
                                            style="
                                                width:
                                                {{
                                                    $criterion[
                                                        'percentage'
                                                    ]
                                                }}%
                                            "
                                        ></div>

                                    </div>


                                    @if (
                                        filled(
                                            $criterion[
                                                'description'
                                            ]
                                        )
                                    )

                                        <p
                                            class="mt-3
                                                   text-xs
                                                   leading-5
                                                   text-zinc-500"
                                        >
                                            {{
                                                $criterion[
                                                    'description'
                                                ]
                                            }}
                                        </p>

                                    @endif


                                    @if (
                                        filled(
                                            $criterion[
                                                'notes'
                                            ]
                                        )
                                    )

                                        <div
                                            class="mt-3
                                                   rounded-lg
                                                   bg-zinc-50
                                                   px-3 py-2
                                                   text-xs
                                                   leading-5
                                                   text-zinc-600
                                                   dark:bg-zinc-800/70
                                                   dark:text-zinc-300"
                                        >
                                            {{
                                                $criterion[
                                                    'notes'
                                                ]
                                            }}
                                        </div>

                                    @endif

                                </div>

                            @empty

                                <div
                                    class="col-span-full
                                           rounded-xl
                                           bg-zinc-50
                                           px-4 py-6
                                           text-center
                                           text-sm
                                           text-zinc-500
                                           dark:bg-zinc-800/50"
                                >
                                    Tidak ada rincian
                                    kriteria.
                                </div>

                            @endforelse

                        </div>


                        {{-- CATATAN UMUM --}}
                        @if (
                            filled(
                                $assessment[
                                    'notes'
                                ]
                            )
                        )

                            <div
                                class="mt-5
                                       rounded-xl
                                       border
                                       border-emerald-100
                                       bg-emerald-50/60
                                       p-4
                                       dark:border-emerald-900/50
                                       dark:bg-emerald-950/20"
                            >

                                <div
                                    class="flex gap-3"
                                >

                                    <flux:icon.chat-bubble-left-ellipsis
                                        class="mt-0.5
                                               size-5
                                               shrink-0
                                               text-emerald-600"
                                    />

                                    <div>

                                        <div
                                            class="text-xs
                                                   font-semibold
                                                   uppercase
                                                   tracking-wide
                                                   text-emerald-700
                                                   dark:text-emerald-300"
                                        >
                                            Catatan Pembina
                                        </div>

                                        <p
                                            class="mt-1
                                                   text-sm
                                                   leading-6
                                                   text-zinc-700
                                                   dark:text-zinc-300"
                                        >
                                            {{
                                                $assessment[
                                                    'notes'
                                                ]
                                            }}
                                        </p>

                                    </div>

                                </div>

                            </div>

                        @endif


                        @if (
                            $assessment[
                                'assessedAt'
                            ]
                        )

                            <div
                                class="mt-4
                                       text-xs
                                       text-zinc-400"
                            >
                                Dinilai pada

                                {{
                                    \Illuminate\Support\Carbon::parse(
                                        $assessment[
                                            'assessedAt'
                                        ]
                                    )
                                        ->locale('id')
                                        ->translatedFormat(
                                            'd F Y, H:i'
                                        )
                                }}
                            </div>

                        @endif

                    </div>

                </article>

            @empty

                {{-- EMPTY --}}
                <div
                    class="rounded-2xl
                           border border-zinc-200
                           bg-white
                           px-6 py-14
                           text-center
                           dark:border-zinc-800
                           dark:bg-zinc-900"
                >

                    <div
                        class="mx-auto
                               flex size-14
                               items-center
                               justify-center
                               rounded-full
                               bg-zinc-100
                               text-zinc-400
                               dark:bg-zinc-800"
                    >
                        <flux:icon.clipboard-document-check
                            class="size-6"
                        />
                    </div>

                    <h2
                        class="mt-4
                               font-semibold
                               text-zinc-900
                               dark:text-white"
                    >
                        Belum ada penilaian
                        keterampilan
                    </h2>

                    <p
                        class="mx-auto mt-1
                               max-w-md
                               text-sm
                               leading-6
                               text-zinc-500"
                    >
                        Penilaian akan muncul setelah
                        pembina mempublikasikan hasil
                        penilaian kegiatan.
                    </p>

                </div>

            @endforelse

        </section>

    @endif

</div>