<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="referrer" content="no-referrer"><title>Penilaian Juri</title>@vite(['resources/css/app.css'])</head>
<body class="bg-zinc-50 text-zinc-900"><main class="mx-auto max-w-5xl space-y-5 p-5">
    <h1 class="text-2xl font-semibold">{{ $assessment->title }}</h1>
    <p>{{ $assessment->activity->title }} · Juri: {{ $judge->name }}</p>
    <p>Isi nilai sesuai kriteria. Simpan draft untuk melanjutkan nanti. Setelah finalisasi, nilai terkunci dan link ini tidak dapat digunakan kembali.</p>
    <p class="text-sm">Batas pengisian: {{ $judge->expires_at->format('d-m-Y H:i') }} ({{ config('app.timezone') }}).</p>
    @if(session('status'))<p role="status" class="rounded bg-green-100 p-3">{{ session('status') }}</p>@endif
    @if($errors->any())<div role="alert" class="rounded bg-red-50 p-3 text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ route('activity-judges.store', ['token' => $token]) }}" class="space-y-5">
        @csrf
        @foreach($assessment->targets as $target)
            <fieldset class="rounded-xl border bg-white p-4"><legend class="px-2 font-semibold">{{ $target->student?->name ?? $target->scoutUnit?->name ?? 'Peserta' }}</legend>
                <div class="grid gap-4 sm:grid-cols-2">@foreach($assessment->criteria as $criterion)
                    <label>{{ $criterion->name }} (maks. {{ $criterion->max_score }}, bobot {{ $criterion->weight }}%)
                        @if($criterion->description)<span class="block text-sm text-zinc-500">{{ $criterion->description }}</span>@endif
                        <input type="number" min="0" max="{{ $criterion->max_score }}" step="0.01" name="scores[{{ $target->id }}][{{ $criterion->id }}]" value="{{ old('scores.'.$target->id.'.'.$criterion->id, $judge->scores[$target->id][$criterion->id] ?? '') }}" class="mt-1 block w-full rounded border p-2">
                    </label>
                @endforeach</div>
            </fieldset>
        @endforeach
        <div class="flex flex-wrap gap-3">
            <button name="action" value="save" class="rounded-lg border px-4 py-2">Simpan Draft</button>
            <button name="action" value="finalize" onclick="return confirm('Finalisasi seluruh nilai? Nilai tidak dapat diubah dan akses link akan ditutup.')" class="rounded-lg bg-amber-700 px-4 py-2 text-white">Finalisasi Nilai</button>
        </div>
    </form>
</main></body></html>
