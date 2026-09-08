<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <span class="break-all font-mono text-sm text-zinc-500">{{ $complaint->reference }}</span>
        <span @class(['rounded-full px-3 py-1 text-xs font-bold', 'bg-amber-100 text-amber-900' => $complaint->status === 'new', 'bg-blue-100 text-blue-900' => $complaint->status === 'processing', 'bg-emerald-100 text-emerald-900' => $complaint->status === 'resolved', 'bg-red-100 text-red-900' => $complaint->status === 'rejected'])>{{ \App\Models\Complaint::STATUSES[$complaint->status] }}</span>
    </div>
    <div><p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">{{ $complaint->category }}</p><h2 class="mt-2 break-words text-2xl font-bold">{{ $complaint->subject }}</h2><p class="mt-2 text-sm text-zinc-500">Dikirim {{ $complaint->created_at->format('d/m/Y H:i') }}</p></div>
    <p class="whitespace-pre-wrap break-words leading-7">{{ $complaint->body }}</p>
    @if ($complaint->response)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100"><h3 class="font-bold">Tanggapan pengelola</h3><p class="mt-3 whitespace-pre-wrap break-words leading-7">{{ $complaint->response }}</p><p class="mt-3 text-xs">Diperbarui {{ $complaint->updated_at->format('d/m/Y H:i') }}</p></div>
    @else
        <p class="rounded-xl bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">Pengaduan telah diterima dan menunggu tanggapan pengelola.</p>
    @endif
</div>
