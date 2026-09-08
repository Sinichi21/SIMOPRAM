<x-layouts::app :title="__('Detail Pengaduan')">
    <div class="mx-auto max-w-4xl space-y-6 p-4 sm:p-6">
        <a href="{{ route('complaints.index') }}" class="inline-block font-semibold text-emerald-700 dark:text-emerald-300">← Kembali ke pengaduan</a>
        @if (session('success'))<p role="status" class="rounded-xl bg-emerald-100 p-4 text-emerald-900">{{ session('success') }}</p>@endif
        <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="mb-5 text-sm text-zinc-500">Pelapor: {{ $complaint->name }} · {{ $complaint->email }}<br>Tujuan: {{ $complaint->school?->name ?? 'Publik / Super admin' }}</p>
            @include('complaints.detail')
            @if ($complaint->attachment_path)<a href="{{ route('complaints.attachment', $complaint->id) }}" class="mt-5 inline-block rounded-lg px-4 py-2" data-action-tone="view">Unduh lampiran bukti</a>@endif
        </section>
        @if ($canManage)
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"><h2 class="mb-5 text-xl font-bold">Tindak lanjut pengaduan</h2><form method="POST" action="{{ route('complaints.update', $complaint->id) }}" class="space-y-5">@csrf @method('PATCH')
                <div><label for="status" class="mb-2 block text-sm font-semibold">Status</label><select id="status" name="status" required class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-zinc-900 focus:border-emerald-600 focus:outline-emerald-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">@foreach (\App\Models\Complaint::STATUSES as $value => $label)<option value="{{ $value }}" @selected(old('status', $complaint->status) === $value)>{{ $label }}</option>@endforeach</select>@error('status')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="response" class="mb-2 block text-sm font-semibold">Tanggapan untuk pelapor</label><textarea id="response" name="response" rows="6" required minlength="10" maxlength="10000" class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-zinc-900 focus:border-emerald-600 focus:outline-emerald-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">{{ old('response', $complaint->response) }}</textarea>@error('response')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <button class="inline-flex items-center justify-center rounded-full bg-emerald-800 px-6 py-3 text-sm font-bold text-white hover:bg-emerald-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-600">Simpan tanggapan</button>
            </form></section>
        @endif
    </div>
</x-layouts::app>
