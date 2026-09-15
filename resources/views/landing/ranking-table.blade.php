<div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
    <table class="w-full text-left text-sm">
        <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"><tr><th scope="col" class="px-4 py-3">Peringkat</th><th scope="col" class="px-4 py-3">Peserta / regu</th><th scope="col" class="px-4 py-3 text-right">Nilai</th></tr></thead>
        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
            @forelse ($rankings as $row)
                <tr><td class="px-4 py-4 font-semibold tabular-nums">{{ $row['rank'] ?? '—' }}</td><td class="px-4 py-4 break-words">{{ $row['name'] }}</td><td class="px-4 py-4 text-right font-semibold tabular-nums">{{ $row['score'] === null ? 'Belum dinilai' : number_format($row['score'], 2) }}</td></tr>
            @empty
                <tr><td colspan="3" class="px-4 py-6 text-center text-zinc-500">Belum ada hasil penilaian.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
