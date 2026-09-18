@if($resultJudges->isNotEmpty())
    <div class="space-y-3">
        <h2 class="text-lg font-semibold">Daftar juri</h2>
        <ul class="grid gap-2 text-sm sm:grid-cols-2">
            @foreach($resultJudges as $judge)
                <li>Juri {{ $judge['number'] }} - {{ $judge['name'] }} @if(! $judge['finalized'])<span class="text-zinc-500">(belum final)</span>@endif</li>
            @endforeach
        </ul>
    </div>
@endif
