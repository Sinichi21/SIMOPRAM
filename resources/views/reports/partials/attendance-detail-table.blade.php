<table class="min-w-full border-collapse text-sm" border="1">
    <thead class="sticky top-0 z-10 bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100">
        <tr>
            <th rowspan="3" class="border p-2">No</th><th rowspan="3" class="min-w-56 border p-2">Nama siswa</th><th rowspan="3" class="border p-2">Kelas</th>
            @foreach ($groups as $group)<th colspan="{{ $group['sessions']->count() + 8 }}" class="border p-2">{{ $group['name'] }}</th>@endforeach
            <th colspan="8" rowspan="2" class="border p-2">Total periode</th>
        </tr>
        <tr>
            @foreach ($groups as $group)
                @foreach ($group['months'] as $month => $sessions)<th colspan="{{ $sessions->count() }}" class="border p-2">{{ $month }}</th>@endforeach
                <th colspan="8" class="border p-2">Rekap semester</th>
            @endforeach
        </tr>
        <tr>
            @foreach ($groups as $group)
                @foreach ($group['sessions'] as $session)<th class="min-w-12 border p-2" title="{{ $session->activity->title }} - {{ $session->name }} - {{ $session->open_at->format('d/m/Y H:i') }}">{{ $session->open_at->format('d') }}<br><small>{{ $session->open_at->format('H:i') }}</small></th>@endforeach
                @foreach (['H','T','S','I','A','?','% hadir','% bobot'] as $label)<th class="border p-2">{{ $label }}</th>@endforeach
            @endforeach
            @foreach (['H','T','S','I','A','?','% hadir','% bobot'] as $label)<th class="border p-2">{{ $label }}</th>@endforeach
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr class="even:bg-zinc-50 dark:even:bg-zinc-900">
                <td class="border p-2">{{ ($students instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $students->firstItem() - 1 : 0) + $loop->iteration }}</td>
                <td class="sticky left-0 border bg-white p-2 dark:bg-zinc-950">{{ $row['student']->name }}</td>
                <td class="whitespace-nowrap border p-2">{{ $row['student']->enrollments->first()?->classroom?->name ?? '-' }}</td>
                @foreach ($groups as $key => $group)
                    @foreach ($group['sessions'] as $session)<td class="border p-2 text-center">{{ $row['cells'][$session->id] }}</td>@endforeach
                    @foreach (['present','late','sick','excused','absent','unrecorded','percentage','weighted'] as $field)<td class="border p-2 text-center">{{ $row['summaries'][$key][$field] ?? '-' }}</td>@endforeach
                @endforeach
                @foreach (['present','late','sick','excused','absent','unrecorded','percentage','weighted'] as $field)<td class="border p-2 text-center font-semibold">{{ $row['total'][$field] ?? '-' }}</td>@endforeach
            </tr>
        @empty <tr><td colspan="{{ 11 + $groups->sum(fn ($group) => $group['sessions']->count() + 8) }}" class="p-4">Tidak ada siswa yang sesuai filter.</td></tr>@endforelse
    </tbody>
</table>
