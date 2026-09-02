<div class="space-y-6">

    <div>

        <h1 class="text-2xl font-semibold">
            Pengumuman Saya
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            Informasi terbaru dari kegiatan Pramuka.
        </p>

    </div>


    <div class="space-y-4">

        @forelse (
            $announcements as $announcement
        )

            <article
                class="rounded-xl border
                       border-zinc-200
                       bg-white p-6
                       shadow-sm
                       dark:border-zinc-800
                       dark:bg-zinc-900"
            >

                <div
                    class="text-xs text-zinc-500"
                >
                    {{ $announcement
                        ->published_at
                        ->format(
                            'd-m-Y H:i'
                        )
                    }}
                </div>


                <h2
                    class="mt-2 text-lg font-semibold"
                >
                    {{ $announcement->title }}
                </h2>


                <div
                    class="mt-3 whitespace-pre-line
                           text-sm leading-6
                           text-zinc-700
                           dark:text-zinc-300"
                >
                    {{ $announcement->body }}
                </div>

            </article>

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
                Belum ada pengumuman untuk Anda.
            </div>

        @endforelse

    </div>

</div>