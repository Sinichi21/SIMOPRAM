@php($tableJudges = ($showJudgeScores ?? false) ? $resultJudges : collect())
<div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-700">
    <table class="w-full text-left text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-800"><tr>
            <th class="px-4 py-3">Peringkat</th><th class="px-4 py-3">Peserta / regu</th>
            @foreach($tableJudges as $judge)<th class="whitespace-nowrap px-4 py-3 text-right">Juri {{ $judge['number'] }}</th>@endforeach
            <th class="px-4 py-3 text-right">{{ ($showJudgeScores ?? false) ? 'Nilai rata-rata' : 'Nilai' }}</th>
        </tr></thead>
        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
            @forelse ($rankings as $row)
                <tr>
                    <td class="px-4 py-4 font-semibold tabular-nums">{{ $row['rank'] ?? '—' }}</td>
                    <td class="break-words px-4 py-4">{{ $row['name'] ?? $row['target']->participant_name ?? $row['target']->student?->name ?? $row['target']->scoutUnit?->name ?? 'Peserta' }}</td>
                    @foreach($tableJudges as $judge)
                        @php($judgeScore = $row['judge_scores'][$judge['id']] ?? null)
                        <td class="px-4 py-4 text-right tabular-nums">{{ $judgeScore === null ? '—' : number_format($judgeScore, 2) }}</td>
                    @endforeach
                    <td class="px-4 py-4 text-right font-semibold tabular-nums">{{ $row['score'] === null ? 'Belum dinilai' : number_format($row['score'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ 3 + $tableJudges->count() }}" class="px-4 py-6 text-center text-zinc-500">Belum ada hasil penilaian.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
