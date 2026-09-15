<!DOCTYPE html><html lang="id"><head>@include('partials.head', ['title' => 'Registrasi '.$activity->title])</head>
<body class="simpram-app min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <x-public-header />
    <main class="app-content mx-auto max-w-5xl space-y-6 px-4 py-8">
        <a class="font-semibold text-emerald-800 dark:text-emerald-300" href="{{ $managed ? route('admin.activity-participants', $activity->id) : route('public.activities.participants', $activity->id) }}">&larr; Kembali ke peserta</a>
        <header class="app-page-heading"><h1 class="text-3xl">{{ $entry ? 'Edit peserta' : 'Registrasi peserta' }}</h1><p class="mt-2">{{ $activity->title }}</p></header>
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-300"><ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" enctype="multipart/form-data" action="{{ $managed ? ($entry ? route('admin.activity-participants.update', [$activity->id, $entry->id]) : route('admin.activity-participants.store', $activity->id)) : route('public.activities.register.store', $activity->id) }}"
            x-data="{ ...@js($formData), blank: @js($blank), limits: @js(collect(\App\Services\ActivityEntryService::CATEGORIES)->mapWithKeys(fn ($label, $key) => [$key => app(\App\Services\ActivityEntryService::class)->limits($activity, $key)])), submitting: false }"
            x-on:submit="submitting = true" class="space-y-6">
            @csrf
            <section class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 dark:bg-zinc-900">
                <h2 class="text-xl font-semibold">Kategori peserta</h2>
                <label class="grid gap-2 text-sm font-medium">Jenis peserta<select name="category" x-model="category" class="rounded-lg border p-2">@foreach ($activity->registration_categories ?: ['individual'] as $category)<option value="{{ $category }}">{{ \App\Services\ActivityEntryService::CATEGORIES[$category] }}</option>@endforeach</select></label>
                <label x-show="category !== 'individual'" class="grid gap-2 text-sm font-medium">Nama kelompok / tim<input name="name" value="{{ old('name', $entry?->name) }}" :disabled="category === 'individual'" :required="category !== 'individual'" maxlength="150" class="rounded-lg border p-2"></label>
                <p class="text-sm text-zinc-500">Anggota utama: <span x-text="limits[category][0]"></span>–<span x-text="limits[category][1]"></span> orang. Setiap peserta/kelompok wajib memiliki satu pembina pendamping; satu cadangan bersifat opsional.</p>
            </section>
            <template x-for="(member, index) in members" :key="index">
                <section class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-3"><h2 class="text-lg font-semibold">Anggota utama <span x-text="index + 1"></span></h2><button type="button" x-show="members.length > 1" x-on:click="members.splice(index, 1)" class="text-sm text-red-600">Hapus anggota</button></div>
                    @include('landing.registration-person', ['person' => 'member', 'prefix' => "'members[' + index + ']'", 'role' => 'student'])
                </section>
            </template>
            <button type="button" x-show="members.length < limits[category][1]" x-on:click="members.push({ ...blank })" class="rounded-lg border border-zinc-300 px-4 py-2">Tambah anggota utama</button>
            <section class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 dark:bg-zinc-900"><h2 class="text-lg font-semibold">Pembina pendamping</h2>@include('landing.registration-person', ['person' => 'coach', 'prefix' => "'coach'", 'role' => 'coach'])</section>
            <section class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 dark:bg-zinc-900">
                <label class="flex items-center gap-3"><input type="checkbox" x-model="hasReserve">Tambahkan satu peserta cadangan</label>
                <template x-if="hasReserve"><div>@include('landing.registration-person', ['person' => 'reserve', 'prefix' => "'reserve'", 'role' => 'student'])</div></template>
            </section>
            @if (count($fields))<section class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 dark:bg-zinc-900"><h2 class="text-xl font-semibold">Informasi tambahan</h2>
                @foreach ($fields as $field)
                    <fieldset class="min-w-0 space-y-2"><legend class="text-sm font-semibold">{{ $field['label'] }} {{ $field['required'] ? '*' : '' }}</legend>
                        @if ($field['description'])<p class="text-sm text-zinc-500">{{ $field['description'] }}</p>@endif
                        @php($answer = old('answers.'.$field['id'], $entry?->answers[$field['id']] ?? null))
                        @if ($field['type'] === 'paragraph')<textarea aria-label="{{ $field['label'] }}" name="answers[{{ $field['id'] }}]" rows="4" @required($field['required']) class="w-full rounded-lg border p-2">{{ $answer }}</textarea>
                        @elseif (in_array($field['type'], ['radio', 'checkbox']))
                            @foreach ($field['options'] as $option)<label class="flex items-center gap-2 text-sm"><input type="{{ $field['type'] }}" name="answers[{{ $field['id'] }}]{{ $field['type'] === 'checkbox' ? '[]' : '' }}" value="{{ $option }}" @checked($field['type'] === 'checkbox' ? in_array($option, (array) $answer, true) : $answer === $option) @required($field['required'] && $field['type'] === 'radio')>{{ $option }}</label>@endforeach
                        @elseif ($field['type'] === 'select')<select aria-label="{{ $field['label'] }}" name="answers[{{ $field['id'] }}]" @required($field['required']) class="w-full rounded-lg border p-2"><option value="">Pilih jawaban</option>@foreach ($field['options'] as $option)<option @selected($answer === $option)>{{ $option }}</option>@endforeach</select>
                        @elseif ($field['type'] === 'file')<input aria-label="{{ $field['label'] }}" type="file" name="files[]" multiple @required($field['required'] && empty($entry?->attachments)) accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx" class="w-full rounded-lg border p-2"><p class="text-xs text-zinc-500">Maksimal 10 file, masing-masing 5 MB. PDF, gambar, atau dokumen Office. Unggahan baru menggantikan lampiran sebelumnya.</p>
                        @else<input aria-label="{{ $field['label'] }}" type="{{ ['date' => 'date', 'time' => 'time'][$field['type']] ?? 'text' }}" name="answers[{{ $field['id'] }}]" value="{{ $answer }}" @required($field['required']) class="w-full rounded-lg border p-2">
                        @endif
                    </fieldset>
                @endforeach
            </section>@endif
            <section class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">Syarat dan ketentuan</h2><p class="whitespace-pre-line text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $entry?->terms_snapshot ?? $activity->registration_terms ?? \App\Services\ActivityEntryService::DEFAULT_TERMS }}</p>
                <label class="flex items-start gap-3 text-sm"><input type="checkbox" name="declaration" value="1" required @checked(old('declaration'))>Saya menyatakan seluruh data dan dokumen asli, benar, serta bertanggung jawab atas isi pendaftaran ini.</label>
                <label class="flex items-start gap-3 text-sm"><input type="checkbox" name="terms" value="1" required @checked(old('terms'))>Saya telah membaca dan menyetujui syarat dan ketentuan kegiatan.</label>
                <button type="submit" :disabled="submitting" class="rounded-lg bg-emerald-800 px-5 py-3 font-semibold text-white disabled:opacity-50">{{ $entry ? 'Simpan Perubahan' : 'Kirim Pendaftaran' }}</button>
                <noscript><p class="text-sm text-red-600">Aktifkan JavaScript untuk mengisi data anggota pada formulir ini.</p></noscript>
            </section>
        </form>
    </main>@fluxScripts
</body></html>
