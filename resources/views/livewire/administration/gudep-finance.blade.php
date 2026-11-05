<div class="mx-auto max-w-7xl space-y-6 px-4 py-5 sm:px-6 lg:py-8">
    {{-- Heading --}}
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-1">
            <p class="text-xs font-semibold uppercase tracking-widest text-emerald-700 dark:text-emerald-400">Administrasi Gugusdepan</p>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-3xl">Keuangan & Sarana</h1>
            <p class="max-w-2xl text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                Kelola anggaran dan buku kas Gudep. Aset, stok, dan peminjaman tetap bersumber dari Inventaris SIMPRAM.
            </p>
        </div>
        <a href="{{ route('modules.show', 'administrasi') }}" wire:navigate
            class="inline-flex shrink-0 items-center justify-center rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800">
            Kembali ke Administrasi
        </a>
    </header>

    @if (session('status'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    {{-- Context filters --}}
    <section aria-label="Lingkup data" class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-5">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-zinc-900 dark:text-zinc-100">Lingkup administrasi</h2>
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Data kas dan anggaran mengikuti Gudep serta tahun ajaran yang dipilih.</p>
            </div>
            <span class="hidden rounded-full bg-zinc-100 px-3 py-1 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 sm:inline-flex">Data per Gudep</span>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label for="finance-scout-group" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Gugusdepan</label>
                <select id="finance-scout-group" wire:model.live="scoutGroupId"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 focus:border-emerald-600 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                    <option value="">Pilih Gudep</option>
                    @foreach ($gudepOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="finance-academic-year" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Tahun ajaran</label>
                <select id="finance-academic-year" wire:model.live="academicYearId"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 focus:border-emerald-600 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                    <option value="">Pilih tahun ajaran</option>
                    @foreach ($years as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    @if ($gudep && $year)
        {{-- Financial overview --}}
        <section aria-label="Ringkasan buku kas" class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5 dark:border-emerald-900 dark:bg-emerald-950/30">
                <p class="text-sm font-medium text-emerald-800 dark:text-emerald-300">Total penerimaan</p>
                <p class="mt-2 break-words text-2xl font-bold tabular-nums tracking-tight text-emerald-950 dark:text-emerald-100">Rp {{ number_format($income, 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-emerald-700 dark:text-emerald-400">Transaksi yang sudah diposting</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">Total pengeluaran</p>
                <p class="mt-2 break-words text-2xl font-bold tabular-nums tracking-tight text-zinc-900 dark:text-zinc-100">Rp {{ number_format($expense, 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Transaksi yang sudah diposting</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-zinc-900 p-5 text-white dark:border-zinc-700 dark:bg-zinc-800">
                <p class="text-sm font-medium text-zinc-300">Saldo berjalan</p>
                <p class="mt-2 break-words text-2xl font-bold tabular-nums tracking-tight text-white">Rp {{ number_format($openingBalance + $income - $expense, 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-zinc-300">Termasuk saldo awal; belum direkonsiliasi dengan kas fisik</p>
            </div>
        </section>

        @if ($cashPeriod?->status === 'closed')
            @can('reports.export')
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-900 dark:bg-emerald-950/30">
                    <div>
                        <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">LPJ Keuangan siap diterbitkan</p>
                        <p class="text-xs text-emerald-700 dark:text-emerald-400">Periode telah ditutup dan disetujui oleh petugas berbeda.</p>
                    </div>
                    <button type="button" wire:click="publishFinanceLpj" wire:loading.attr="disabled" wire:target="publishFinanceLpj"
                        class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">
                        Terbitkan LPJ Keuangan PDF
                    </button>
                </div>
            @endcan
        @endif

        {{-- Annual financial period --}}
        <section aria-labelledby="finance-period-heading" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Pengendalian periode</p>
                    <h2 id="finance-period-heading" class="mt-1 text-lg font-semibold">Saldo awal & tutup buku</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Saldo awal ditetapkan sebelum transaksi pertama diposting. Tutup buku mengunci tahun ajaran ini.</p>
                </div>
                @if ($cashPeriod?->status === 'closed')
                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">Periode ditutup</span>
                @else
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Periode terbuka</span>
                @endif
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Saldo awal</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">Rp {{ number_format($openingBalance, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ $cashPeriod?->opening_set_at ? 'Ditetapkan '.$cashPeriod->opening_set_at->format('d/m/Y H:i') : 'Belum ditetapkan (diasumsikan nol)' }}</p>
                </div>
                <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $cashPeriod?->status === 'closed' ? 'Saldo akhir terkunci' : 'Saldo berjalan termasuk saldo awal' }}</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">Rp {{ number_format($openingBalance + $income - $expense, 0, ',', '.') }}</p>
                    @if ($cashPeriod?->closed_at)
                        <p class="mt-1 text-xs text-zinc-500">Ditutup {{ $cashPeriod->closed_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </div>
            @if ($cashPeriod?->status === 'closed')
                <p class="mt-3 break-all text-xs text-zinc-500 dark:text-zinc-400">Checksum penutupan: {{ $cashPeriod->closing_checksum }}</p>
            @elseif (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.finance.manage'))
                @if (! $cashPeriod?->opening_set_at)
                    <form wire:submit="saveOpeningBalance" class="mt-4 flex flex-wrap items-end gap-3">
                        <div>
                            <label for="finance-opening-balance" class="mb-1 block text-sm font-medium">Tetapkan saldo awal (Rp)</label>
                            <input id="finance-opening-balance" type="number" min="0" step="1" wire:model="openingBalanceInput"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800" required>
                            @error('openingBalanceInput') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" wire:confirm="Tetapkan saldo awal? Nilai ini tidak dapat diubah setelah tersimpan." class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Tetapkan saldo awal</button>
                    </form>
                @endif
                @if ($cashPeriod?->opening_set_at)
                    <div class="mt-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <h3 class="text-sm font-semibold">Rekonsiliasi kas fisik</h3>
                        <p class="mt-1 text-xs text-zinc-500">Bandingkan uang yang dihitung dengan saldo buku. Selisih harus nol, dan transaksi tidak boleh berubah setelah pemeriksaan untuk bisa tutup buku.</p>
                        @if ($latestReconciliation)
                            <div class="mt-3 grid gap-2 text-sm sm:grid-cols-3">
                                <div>Saldo buku: <strong>Rp {{ number_format($latestReconciliation->book_balance, 0, ',', '.') }}</strong></div>
                                <div>Kas fisik: <strong>Rp {{ number_format($latestReconciliation->counted_cash, 0, ',', '.') }}</strong></div>
                                <div>Selisih: <strong class="{{ $latestReconciliation->difference === 0 ? 'text-emerald-700' : 'text-red-600' }}">Rp {{ number_format($latestReconciliation->difference, 0, ',', '.') }}</strong></div>
                            </div>
                        @endif
                        @if ($cashPeriod?->close_requested_at === null)
                        <form wire:submit="reconcileCash" class="mt-3 grid gap-3 md:grid-cols-2">
                            <div>
                                <label for="cash-counted-balance" class="mb-1 block text-sm font-medium">Jumlah kas fisik (Rp)</label>
                                <input id="cash-counted-balance" type="number" min="0" step="1" wire:model="countedCashInput" required
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                                @error('countedCashInput') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="cash-reconciliation-notes" class="mb-1 block text-sm font-medium">Catatan pemeriksaan</label>
                                <textarea id="cash-reconciliation-notes" wire:model="reconciliationNotesInput" rows="2" required maxlength="3000"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"></textarea>
                                @error('reconciliationNotesInput') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <button type="submit" wire:loading.attr="disabled" wire:target="reconcileCash"
                                    class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Catat rekonsiliasi</button>
                            </div>
                        </form>
                        @endif
                    </div>
                @if ($cashPeriod?->close_requested_at)
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                        <p class="font-semibold">Menunggu persetujuan penutupan</p>
                        <p class="mt-1">Diajukan {{ $cashPeriod->close_requested_at->format('d/m/Y H:i') }}. Saldo yang diajukan: Rp {{ number_format($cashPeriod->close_requested_balance, 0, ',', '.') }}.</p>
                        <p class="mt-1">Transaksi dan perubahan anggaran dibekukan sampai keputusan diberikan.</p>
                        @if (auth()->user()?->can('gudep.finance.close.approve') && (int) $cashPeriod->close_requested_by !== (int) auth()->id())
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" wire:click="approveCashPeriodClosure" wire:confirm="Setujui dan kunci buku kas tahun ajaran ini secara permanen?" class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white">Setujui tutup buku</button>
                            </div>
                            <form wire:submit="rejectCashPeriodClosure" class="mt-3 space-y-2 rounded-lg border border-amber-300 p-3 dark:border-amber-800">
                                <label for="cash-close-rejection-reason" class="block text-xs font-semibold">Alasan penolakan (wajib, minimal 10 karakter)</label>
                                <textarea id="cash-close-rejection-reason" wire:model="closeRejectionReason" rows="2" maxlength="3000" required
                                    class="w-full rounded-lg border border-amber-300 bg-white p-2.5 text-sm text-zinc-900 dark:border-amber-800 dark:bg-zinc-900 dark:text-zinc-100"
                                    placeholder="Jelaskan data atau bukti yang harus diperbaiki sebelum mengajukan ulang..."></textarea>
                                @error('closeRejectionReason') <p class="text-xs text-red-700 dark:text-red-400">{{ $message }}</p> @enderror
                                <button type="submit" wire:loading.attr="disabled" wire:target="rejectCashPeriodClosure"
                                    wire:confirm="Tolak permohonan dengan alasan ini dan buka kembali pengelolaan kas?"
                                    class="rounded-lg border border-amber-700 px-4 py-2 text-xs font-semibold hover:bg-amber-100 disabled:opacity-50 dark:hover:bg-amber-950">
                                    Tolak dengan alasan
                                </button>
                            </form>
                        @endif
                    </div>
                @elseif ($cashPeriod?->opening_set_at)
                    <details class="mt-4 rounded-xl border border-amber-200 p-4 dark:border-amber-900">
                        <summary class="cursor-pointer text-sm font-semibold text-amber-800 dark:text-amber-300">Ajukan tutup buku tahun ajaran</summary>
                        <form wire:submit="closeCashPeriod" class="mt-3 space-y-3">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Seluruh draf harus diselesaikan dan rekonsiliasi terakhir harus cocok dengan saldo buku. Setelah ditutup, anggaran, transaksi, pembalikan dan lampiran bukti tidak bisa diubah.</p>
                            <label for="finance-close-notes" class="block text-sm font-medium">Catatan penutupan (min. 10 karakter)</label>
                            <textarea id="finance-close-notes" wire:model="closingNotesInput" rows="2" maxlength="3000" required class="w-full rounded-lg border border-zinc-300 bg-white p-3 text-sm dark:border-zinc-700 dark:bg-zinc-800"></textarea>
                            @error('closingNotesInput') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            <button type="submit" wire:confirm="Ajukan penutupan untuk diperiksa dan disetujui petugas berbeda?" class="rounded-lg bg-amber-800 px-4 py-2 text-sm font-semibold text-white">Ajukan penutupan</button>
                        </form>
                    </details>
                @endif
                @endif
            @endif
        </section>

        @if ($closeDecisions->isNotEmpty())
            <section aria-labelledby="cash-decision-history-heading" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 id="cash-decision-history-heading" class="text-base font-semibold">Riwayat permohonan & keputusan tutup buku</h2>
                <p class="mt-1 text-xs text-zinc-500">Riwayat tersimpan permanen, termasuk permohonan yang pernah ditolak (30 peristiwa terbaru).</p>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead><tr class="border-b border-zinc-200 text-zinc-500 dark:border-zinc-700">
                            <th class="py-2 pr-3">Waktu</th><th class="py-2 pr-3">Keputusan</th><th class="py-2 pr-3">Pelaksana</th><th class="py-2">Catatan</th>
                        </tr></thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($closeDecisions as $decision)
                                <tr>
                                    <td class="whitespace-nowrap py-2 pr-3">{{ $decision->decided_at?->format('d/m/Y H:i') }}</td>
                                    <td class="py-2 pr-3 font-medium">{{ ['requested' => 'Diajukan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$decision->action] ?? $decision->action }}</td>
                                    <td class="py-2 pr-3">User #{{ $decision->actor_id ?? '-' }}</td>
                                    <td class="py-2">{{ $decision->notes ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- Budget --}}
        <section id="anggaran" aria-labelledby="finance-budget-heading" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-4 py-5 dark:border-zinc-800 sm:px-6">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">01 / Perencanaan</p>
                    <h2 id="finance-budget-heading" class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Rencana anggaran</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Alokasi per kategori untuk {{ $year->name }}.</p>
                </div>
                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $budgets->count() }} kategori</span>
            </div>

            @if ($cashPeriod?->status !== 'closed' && $cashPeriod?->close_requested_at === null && (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.finance.manage')))
                <details class="group border-b border-zinc-100 dark:border-zinc-800">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-4 text-sm font-semibold text-emerald-700 hover:bg-zinc-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600 dark:text-emerald-400 dark:hover:bg-zinc-800/50 sm:px-6">
                        <span>Tambah atau perbarui anggaran</span>
                        <span aria-hidden="true" class="text-lg transition-transform group-open:rotate-45">+</span>
                    </summary>
                    <form wire:submit="saveBudget" class="grid gap-4 bg-zinc-50/60 px-4 pb-5 pt-2 dark:bg-zinc-800/30 sm:px-6 md:grid-cols-2">
                        <div>
                            <label for="finance-budget-category" class="mb-1.5 block text-sm font-medium">Kategori anggaran <span class="text-red-600">*</span></label>
                            <input id="finance-budget-category" wire:model="budgetCategory" maxlength="100" required placeholder="Contoh: Latihan rutin"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            @error('budgetCategory') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="finance-budget-amount" class="mb-1.5 block text-sm font-medium">Alokasi (Rp) <span class="text-red-600">*</span></label>
                            <input id="finance-budget-amount" wire:model="budgetAmount" type="number" inputmode="numeric" min="0" step="1" required placeholder="0"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            @error('budgetAmount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="finance-budget-notes" class="mb-1.5 block text-sm font-medium">Keterangan <span class="font-normal text-zinc-500">(opsional)</span></label>
                            <textarea id="finance-budget-notes" wire:model="budgetNotes" rows="2" maxlength="2000" placeholder="Keterangan alokasi anggaran"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" wire:loading.attr="disabled" wire:target="saveBudget"
                                class="inline-flex items-center justify-center rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">
                                Simpan anggaran
                            </button>
                        </div>
                    </form>
                </details>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800/50 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold sm:px-6">Kategori</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Alokasi</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Realisasi</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Sisa / Selisih</th>
                            <th scope="col" class="hidden px-4 py-3 font-semibold md:table-cell sm:px-6">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($budgets as $budget)
                            <tr>
                                <td class="px-4 py-3 font-medium sm:px-6">{{ $budget->category }}</td>
                                @php
                                    $realized = (int) ($expenseByCategory[$budget->category] ?? 0);
                                    $remaining = (int) $budget->allocated_amount - $realized;
                                @endphp
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">Rp {{ number_format($budget->allocated_amount, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">Rp {{ number_format($realized, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums {{ $remaining < 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">Rp {{ number_format($remaining, 0, ',', '.') }}</td>
                                <td class="hidden px-4 py-3 text-zinc-500 dark:text-zinc-400 md:table-cell sm:px-6">{{ $budget->notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">Belum ada anggaran untuk tahun ajaran ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="border-t border-zinc-100 px-4 py-3 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400 sm:px-6">Realisasi adalah seluruh pengeluaran yang sudah diposting pada kategori yang persis sama. Pengeluaran tanpa kategori anggaran tetap tercatat dalam Buku Kas, tetapi tidak masuk salah satu baris anggaran.</p>
        </section>

        {{-- Cash ledger --}}
        <section id="buku-kas" aria-labelledby="finance-ledger-heading" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-4 py-5 dark:border-zinc-800 sm:px-6">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">02 / Pencatatan</p>
                    <h2 id="finance-ledger-heading" class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Buku kas</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Transaksi draf tidak dihitung dalam saldo sampai diposting.</p>
                </div>
                @can('reports.export')
                    <button type="button" wire:click="publishCashBook" wire:loading.attr="disabled" wire:target="publishCashBook"
                        class="rounded-lg border border-emerald-700 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 disabled:opacity-50 dark:text-emerald-400 dark:hover:bg-emerald-950">
                        Terbitkan buku kas PDF
                    </button>
                @endcan
            </div>

            @if ($cashPeriod?->status !== 'closed' && $cashPeriod?->close_requested_at === null && (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.finance.manage')))
                <details id="cash-entry-form" @if ($editingCashId !== null) open @endif class="group border-b border-zinc-100 dark:border-zinc-800">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-4 text-sm font-semibold text-emerald-700 hover:bg-zinc-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600 dark:text-emerald-400 dark:hover:bg-zinc-800/50 sm:px-6">
                        <span>{{ $editingCashId !== null ? 'Edit draf transaksi #'.$editingCashId : 'Catat transaksi baru' }}</span>
                        <span aria-hidden="true" class="text-lg transition-transform group-open:rotate-45">+</span>
                    </summary>
                    <form wire:submit="saveCash" class="space-y-5 bg-zinc-50/60 px-4 pb-5 pt-2 dark:bg-zinc-800/30 sm:px-6">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label for="finance-cash-direction" class="mb-1.5 block text-sm font-medium">Jenis transaksi</label>
                                <select id="finance-cash-direction" wire:model="cashDirection" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                    <option value="income">Penerimaan</option>
                                    <option value="expense">Pengeluaran</option>
                                </select>
                            </div>
                            <div>
                                <label for="finance-cash-date" class="mb-1.5 block text-sm font-medium">Tanggal <span class="text-red-600">*</span></label>
                                <input id="finance-cash-date" wire:model="cashDate" type="date" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                @error('cashDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="finance-cash-category" class="mb-1.5 block text-sm font-medium">Kategori <span class="text-red-600">*</span></label>
                                <select id="finance-cash-category" wire:model="cashCategory" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                    <option value="">Pilih kategori transaksi</option>
                                    @foreach ($cashCategoryOptions as $category)
                                        <option value="{{ $category }}">{{ $category }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Kategori diambil dari anggaran dan transaksi sebelumnya. Untuk kategori baru, tambahkan dulu di Rencana Anggaran.</p>
                                @error('cashCategory') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label for="finance-cash-description" class="mb-1.5 block text-sm font-medium">Uraian <span class="text-red-600">*</span></label>
                                <input id="finance-cash-description" wire:model="cashDescription" maxlength="255" required placeholder="Deskripsi transaksi" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                @error('cashDescription') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="finance-cash-amount" class="mb-1.5 block text-sm font-medium">Jumlah (Rp) <span class="text-red-600">*</span></label>
                                <input id="finance-cash-amount" wire:model="cashAmount" type="number" min="1" step="1" inputmode="numeric" required placeholder="0" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                @error('cashAmount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="finance-cash-reference" class="mb-1.5 block text-sm font-medium">Nomor bukti <span class="font-normal text-zinc-500">(opsional)</span></label>
                                <input id="finance-cash-reference" wire:model="cashReference" maxlength="100" placeholder="Nomor kuitansi / nota" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            </div>
                            <div>
                                <label for="finance-cash-activity" class="mb-1.5 block text-sm font-medium">Kegiatan terkait</label>
                                <select id="finance-cash-activity" wire:model="cashActivityId" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                    <option value="">Tidak ditautkan</option>
                                    @foreach ($activities as $activity)
                                        <option value="{{ $activity->id }}">{{ $activity->title }}</option>
                                    @endforeach
                                </select>
                                @error('cashActivityId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            @can('inventory.view')
                                <div>
                                    <label for="finance-cash-item" class="mb-1.5 block text-sm font-medium">Referensi barang</label>
                                    <select id="finance-cash-item" wire:model="cashInventoryItemId" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                        <option value="">Tidak ditautkan</option>
                                        @foreach ($inventoryOptions as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('cashInventoryItemId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @endcan
                            <div class="md:col-span-3">
                                <label for="finance-cash-notes" class="mb-1.5 block text-sm font-medium">Catatan tambahan</label>
                                <textarea id="finance-cash-notes" wire:model="cashNotes" rows="2" maxlength="3000" placeholder="Informasi tambahan (opsional)"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900"></textarea>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <button type="submit" wire:loading.attr="disabled" wire:target="saveCash"
                                class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">{{ $editingCashId !== null ? 'Simpan perubahan draf' : 'Simpan sebagai draf' }}</button>
                            @if ($editingCashId !== null)
                                <button type="button" wire:click="cancelCashDraftEdit" class="rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-medium dark:border-zinc-700">Batal edit</button>
                            @endif
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">Draf tidak memengaruhi saldo buku kas.</span>
                        </div>
                    </form>
                </details>
            @endif

            @if ($cashPeriod?->status !== 'closed' && $cashPeriod?->close_requested_at === null && (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.finance.manage')))
                <details class="group border-b border-zinc-100 dark:border-zinc-800">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-4 text-sm font-semibold text-emerald-700 hover:bg-zinc-50 dark:text-emerald-400 dark:hover:bg-zinc-800/50 sm:px-6">
                        <span>Lampirkan nota atau kuitansi</span>
                        <span aria-hidden="true" class="text-lg transition-transform group-open:rotate-45">+</span>
                    </summary>
                    <form wire:submit="uploadCashEvidence" class="grid gap-4 bg-zinc-50/60 px-4 pb-5 pt-3 dark:bg-zinc-800/30 sm:px-6 md:grid-cols-3">
                        <div>
                            <label for="finance-receipt-entry" class="mb-1.5 block text-sm font-medium">Transaksi kas</label>
                            <select id="finance-receipt-entry" wire:model="receiptCashEntryId" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                <option value="">Pilih transaksi</option>
                                @foreach ($entries as $entry)
                                    <option value="{{ $entry->id }}">{{ $entry->occurred_at?->format('d/m/Y') }} · {{ $entry->description }} · Rp {{ number_format($entry->amount, 0, ',', '.') }}</option>
                                @endforeach
                            </select>
                            @error('receiptCashEntryId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="finance-receipt-file" class="mb-1.5 block text-sm font-medium">Bukti PDF/JPG/PNG (maks. 8 MB)</label>
                            <input id="finance-receipt-file" type="file" wire:model="receiptAttachment" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm">
                            @error('receiptAttachment') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex items-end">
                            <button type="submit" wire:loading.attr="disabled" wire:target="receiptAttachment,uploadCashEvidence" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">Arsipkan bukti</button>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 md:col-span-3">Lampiran tersimpan privat. Mengunggah bukti baru tidak menghapus bukti sebelumnya. Pilih transaksi dari daftar yang sedang ditampilkan.</p>
                    </form>
                </details>
            @endif

            @if ($reversalSelectedEntry && $cashPeriod?->status !== 'closed' && $cashPeriod?->close_requested_at === null && (auth()->user()?->can('gudep.finance.manage') || auth()->user()?->can('gudep.manage')))
                <form wire:submit="reverseCash" class="space-y-3 border-b border-amber-200 bg-amber-50/70 px-4 py-5 dark:border-amber-900 dark:bg-amber-950/30 sm:px-6">
                    <div>
                        <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-200">Koreksi transaksi #{{ $reversalSelectedEntry->id }}</h3>
                        <p class="mt-1 text-xs text-amber-800 dark:text-amber-300">{{ $reversalSelectedEntry->description }} · Rp {{ number_format($reversalSelectedEntry->amount, 0, ',', '.') }}</p>
                        <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">Transaksi asli tidak dihapus. Pembalikan masuk sebagai entri baru yang diposting dan dapat ditelusuri.</p>
                    </div>
                    <div>
                        <label for="finance-reversal-reason" class="mb-1.5 block text-sm font-medium">Alasan koreksi (minimal 10 karakter)</label>
                        <textarea id="finance-reversal-reason" wire:model="reversalReason" rows="2" maxlength="1000" required class="w-full rounded-lg border border-amber-300 bg-white px-3 py-2.5 text-sm dark:border-amber-800 dark:bg-zinc-900" placeholder="Jelaskan alasan pembalikan transaksi..."></textarea>
                        @error('reversalReason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" wire:confirm="Buat transaksi pembalik resmi? Transaksi asli tetap tersimpan." wire:loading.attr="disabled" wire:target="reverseCash" class="rounded-lg bg-amber-800 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-900 disabled:opacity-50">Konfirmasi pembalikan</button>
                </form>
            @endif

            <div class="space-y-3 border-b border-zinc-100 px-4 py-4 dark:border-zinc-800 sm:px-6">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="sm:col-span-2">
                        <label for="finance-ledger-search" class="mb-1.5 block text-xs font-medium text-zinc-600 dark:text-zinc-300">Cari transaksi</label>
                        <input id="finance-ledger-search" type="search" wire:model.live.debounce.350ms="ledgerSearch"
                            placeholder="Cari uraian, kategori, atau nomor bukti..."
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                    </div>
                    <div>
                        <label for="finance-ledger-direction" class="mb-1.5 block text-xs font-medium text-zinc-600 dark:text-zinc-300">Jenis transaksi</label>
                        <select id="finance-ledger-direction" wire:model.live="ledgerDirection"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                            <option value="all">Semua jenis</option>
                            <option value="income">Penerimaan</option>
                            <option value="expense">Pengeluaran</option>
                        </select>
                    </div>
                    <div>
                        <label for="finance-ledger-status" class="mb-1.5 block text-xs font-medium text-zinc-600 dark:text-zinc-300">Status</label>
                        <select id="finance-ledger-status" wire:model.live="ledgerStatus"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                            <option value="all">Semua status</option>
                            <option value="draft">Draf</option>
                            <option value="posted">Tercatat</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                    <span>Menampilkan {{ $entries->count() }} dari {{ number_format($ledgerMatches, 0, ',', '.') }} transaksi yang sesuai (maksimum 100 baris).</span>
                    <button type="button" wire:click="clearLedgerFilters" class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">Reset filter</button>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Filter hanya mengubah tabel, bukan angka ringkasan saldo atau isi PDF resmi. Pembalikan dan transaksi asli ditampilkan sebagai jejak audit.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800/50 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold sm:px-6">Tanggal</th>
                            <th scope="col" class="min-w-48 px-4 py-3 font-semibold">Transaksi</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Jenis</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-semibold">Jumlah</th>
                            <th scope="col" class="px-4 py-3 font-semibold sm:px-6">Status / Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($entries as $entry)
                            <tr wire:key="cash-{{ $entry->id }}" class="align-top transition hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40">
                                <td class="whitespace-nowrap px-4 py-4 text-zinc-600 dark:text-zinc-300 sm:px-6">{{ $entry->occurred_at?->format('d/m/Y') }}</td>
                                <td class="px-4 py-4">
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $entry->description }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $entry->category }} @if ($entry->reference_number) · {{ $entry->reference_number }} @endif</p>
                                    @foreach ($entry->evidences as $evidence)
                                        <a href="{{ route('administration.finance.evidence.download', $evidence->id) }}" class="mt-1 block text-xs font-medium text-emerald-700 hover:underline dark:text-emerald-400">Bukti: {{ $evidence->original_filename }}</a>
                                    @endforeach
                                </td>
                                <td class="px-4 py-4">
                                    @if ($entry->direction === 'income')
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Masuk</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">Keluar</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-semibold tabular-nums {{ $entry->direction === 'income' ? 'text-emerald-700 dark:text-emerald-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                    {{ $entry->direction === 'income' ? '+' : '−' }} Rp {{ number_format($entry->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 sm:px-6">
                                    @if ($entry->status === 'posted')
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Tercatat</span>
                                        @if ($entry->reversal_of_id !== null)
                                            <span class="mt-1 block text-xs text-amber-700 dark:text-amber-300">Pembalikan #{{ $entry->reversal_of_id }}</span>
                                            <span class="mt-1 block text-xs text-zinc-500">{{ $entry->reversal_reason }}</span>
                                        @elseif ($entry->reversals_exists)
                                            <span class="mt-1 block text-xs text-amber-700 dark:text-amber-300">Sudah dibalik</span>
                                        @elseif ($cashPeriod?->status !== 'closed' && $cashPeriod?->close_requested_at === null && (auth()->user()?->can('gudep.finance.manage') || auth()->user()?->can('gudep.manage')))
                                            <button type="button" wire:click="selectCashForReversal({{ $entry->id }})" class="mt-2 block text-xs font-semibold text-amber-700 hover:underline dark:text-amber-300">Ajukan pembalikan</button>
                                        @endif
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-300">Draf</span>
                                        @if ($cashPeriod?->status !== 'closed' && $cashPeriod?->close_requested_at === null && (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.finance.manage')))
                                            <button type="button" wire:click="editCashDraft({{ $entry->id }})" class="mt-2 block text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">Edit draf</button>
                                            <button type="button" wire:click="deleteCashDraft({{ $entry->id }})" wire:confirm="Hapus draf transaksi ini? Tindakan ini tidak dapat dibatalkan." class="mt-2 block text-xs font-semibold text-red-700 hover:underline dark:text-red-400">Batalkan / hapus draf</button>
                                        @endif
                                        @if ($cashPeriod?->status !== 'closed' && $cashPeriod?->close_requested_at === null && (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.finance.manage')))
                                            <button type="button" wire:click="postCash({{ $entry->id }})" wire:confirm="Posting transaksi ini ke buku kas?" wire:loading.attr="disabled" wire:target="postCash({{ $entry->id }})"
                                                class="mt-2 block rounded-md border border-emerald-600 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 disabled:opacity-50 dark:text-emerald-400 dark:hover:bg-emerald-950">
                                                Posting transaksi
                                            </button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-zinc-500">Tidak ada transaksi yang sesuai. Ubah atau reset filter untuk melihat transaksi lain.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Existing inventory read-only summary --}}
        <section id="sarana" aria-labelledby="finance-inventory-heading" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="border-b border-zinc-100 px-4 py-5 dark:border-zinc-800 sm:px-6">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">03 / Inventaris</p>
                <h2 id="finance-inventory-heading" class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Sarana milik Gudep</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Ringkasan dari modul Inventaris. Transaksi peminjaman, pengembalian, dan perubahan stok dikelola di sana.</p>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Barang milik sekolah tersedia untuk Gudep, tetapi kepemilikannya tidak otomatis berpindah. Pilih filter untuk membedakannya.</p>
            </div>
            @can('inventory.view')
                <div class="flex flex-wrap items-center gap-3 px-4 pt-5 sm:px-6">
                    <label for="finance-inventory-scope" class="text-sm font-medium">Cakupan inventaris</label>
                    <select id="finance-inventory-scope" wire:model.live="inventoryScope" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                        <option value="all">Sekolah + Gudep</option>
                        <option value="gudep">Khusus milik Gudep</option>
                    </select>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $inventoryOwners->count() }} pemilik inventaris sekolah/Gudep terdaftar</span>
                </div>
                <div class="grid gap-3 p-4 sm:grid-cols-3 sm:p-6">
                    <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/60"><p class="text-xs text-zinc-500 dark:text-zinc-400">Aset ditampilkan</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $assets->count() }}</p></div>
                    <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/60"><p class="text-xs text-zinc-500 dark:text-zinc-400">Baris stok ditampilkan</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $stocks->count() }}</p></div>
                    <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/60"><p class="text-xs text-zinc-500 dark:text-zinc-400">Peminjaman terkait</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $loanCount }}</p></div>
                </div>
                <div class="flex flex-wrap gap-3 px-4 pb-5 sm:px-6">
                    <a href="{{ route('inventory.index') }}" wire:navigate class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Kelola inventaris</a>
                    <a href="{{ route('inventory.loans.index') }}" wire:navigate class="rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">Transaksi peminjaman</a>
                </div>
                <div class="grid border-t border-zinc-100 dark:border-zinc-800 md:grid-cols-2">
                    <div class="p-4 sm:p-6">
                        <h3 class="mb-3 text-sm font-semibold">Daftar aset</h3>
                        <ul class="space-y-2 text-sm">
                            @forelse ($assets as $asset)
                                <li class="flex justify-between gap-3 rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                                    <span class="min-w-0"><span class="block truncate font-medium">{{ $asset->item?->name ?? 'Barang tanpa nama' }}</span><span class="block text-xs text-zinc-500">{{ $asset->owner?->display_name }}</span></span>
                                    <span class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">{{ $asset->asset_code }}</span>
                                </li>
                            @empty
                                <li class="rounded-lg border border-dashed border-zinc-200 p-5 text-center text-zinc-500 dark:border-zinc-700">Belum ada aset Gudep.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="border-t border-zinc-100 p-4 dark:border-zinc-800 sm:p-6 md:border-l md:border-t-0">
                        <h3 class="mb-3 text-sm font-semibold">Stok barang</h3>
                        <ul class="space-y-2 text-sm">
                            @forelse ($stocks as $stock)
                                <li class="flex items-center justify-between gap-3 rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                                    <span class="min-w-0"><span class="block truncate font-medium">{{ $stock->item?->name ?? 'Barang tanpa nama' }}</span><span class="block text-xs text-zinc-500">{{ $stock->owner?->display_name }}</span></span>
                                    <span class="shrink-0 font-semibold tabular-nums">{{ $stock->quantity_on_hand }} <span class="font-normal text-zinc-500">{{ $stock->item?->unit }}</span></span>
                                </li>
                            @empty
                                <li class="rounded-lg border border-dashed border-zinc-200 p-5 text-center text-zinc-500 dark:border-zinc-700">Belum ada stok Gudep.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
                <p class="border-t border-zinc-100 px-4 py-3 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400 sm:px-6">Maksimum 100 aset dan 100 baris stok ditampilkan. Angka ini bukan total keseluruhan jika data lebih banyak.</p>
            @else
                <div class="p-6 text-sm text-zinc-500 dark:text-zinc-400">Detail sarana hanya tersedia untuk pengguna dengan izin melihat inventaris.</div>
            @endcan
        </section>
    @else
        <div class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Pilih lingkup administrasi</h2>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Pilih Gugusdepan dan tahun ajaran pada bagian atas untuk menampilkan anggaran, buku kas, serta sarana.</p>
        </div>
    @endif
</div>
