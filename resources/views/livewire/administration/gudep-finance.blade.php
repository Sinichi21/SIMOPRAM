<div class="mx-auto max-w-7xl space-y-6 p-6">
    <h1 class="text-2xl font-bold">Buku Kas, Anggaran & Sarana Gudep</h1>
    <p class="text-sm text-zinc-500">Keuangan Gudep memakai Rupiah bulat; stok dan peminjaman tetap di Inventaris SIMPRAM.</p>
    @if (session('status'))<p role="status" class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900">{{ session('status') }}</p>@endif
    <div class="grid gap-3 md:grid-cols-2">
        <label>Gudep<select wire:model.live="scoutGroupId" class="w-full rounded-lg border p-2 dark:bg-zinc-900"><option value="">Pilih Gudep</option>@foreach ($gudepOptions as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach</select></label>
        <label>Tahun Ajaran<select wire:model.live="academicYearId" class="w-full rounded-lg border p-2 dark:bg-zinc-900"><option value="">Pilih Tahun Ajaran</option>@foreach ($years as $y)<option value="{{ $y->id }}">{{ $y->name }}</option>@endforeach</select></label>
    </div>
    @if ($gudep && $year)
        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-lg border p-4">Penerimaan terposting: <strong>Rp {{ number_format($income, 0, ',', '.') }}</strong></div>
            <div class="rounded-lg border p-4">Pengeluaran terposting: <strong>Rp {{ number_format($expense, 0, ',', '.') }}</strong></div>
            <div class="rounded-lg border p-4">Saldo: <strong>Rp {{ number_format($income - $expense, 0, ',', '.') }}</strong></div>
        </div>
        <section class="rounded-xl border p-5">
            <h2 class="text-lg font-semibold">1. Rencana Anggaran</h2>
            @if (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.finance.manage'))
                <form wire:submit="saveBudget" class="mt-3 grid gap-3 md:grid-cols-3">
                    <label>Kategori<input wire:model="budgetCategory" required maxlength="100" class="w-full rounded-lg border p-2 dark:bg-zinc-900">@error('budgetCategory')<span class="text-red-600">{{ $message }}</span>@enderror</label>
                    <label>Alokasi Rp<input type="number" wire:model="budgetAmount" required min="0" step="1" class="w-full rounded-lg border p-2 dark:bg-zinc-900">@error('budgetAmount')<span class="text-red-600">{{ $message }}</span>@enderror</label>
                    <label>Catatan<input wire:model="budgetNotes" maxlength="2000" class="w-full rounded-lg border p-2 dark:bg-zinc-900"></label>
                    <button class="rounded-lg bg-emerald-700 px-4 py-2 text-white">Simpan Anggaran</button>
                </form>
            @endif
            <table class="mt-4 w-full text-left text-sm"><thead><tr><th>Kategori</th><th>Alokasi</th></tr></thead><tbody>
                @forelse ($budgets as $b)<tr class="border-t"><td class="p-2">{{ $b->category }}</td><td class="p-2">Rp {{ number_format($b->allocated_amount, 0, ',', '.') }}</td></tr>
                @empty<tr><td colspan="2" class="p-3">Belum ada anggaran.</td></tr>@endforelse
            </tbody></table>
        </section>
        <section class="rounded-xl border p-5">
            <h2 class="text-lg font-semibold">2. Buku Kas</h2>
            <p class="text-sm text-zinc-500">Simpan draf lalu posting. Transaksi yang diposting tidak dapat diubah dalam MVP ini.</p>
            @if (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.finance.manage'))
                <form wire:submit="saveCash" class="mt-3 grid gap-3 md:grid-cols-3">
                    <label>Jenis<select wire:model="cashDirection" class="w-full rounded-lg border p-2 dark:bg-zinc-900"><option value="income">Penerimaan</option><option value="expense">Pengeluaran</option></select></label>
                    <label>Tanggal<input type="date" wire:model="cashDate" required class="w-full rounded-lg border p-2 dark:bg-zinc-900">@error('cashDate')<span class="text-red-600">{{ $message }}</span>@enderror</label>
                    <label>Kategori<input wire:model="cashCategory" required maxlength="100" class="w-full rounded-lg border p-2 dark:bg-zinc-900">@error('cashCategory')<span class="text-red-600">{{ $message }}</span>@enderror</label>
                    <label>Uraian<input wire:model="cashDescription" required maxlength="255" class="w-full rounded-lg border p-2 dark:bg-zinc-900">@error('cashDescription')<span class="text-red-600">{{ $message }}</span>@enderror</label>
                    <label>Jumlah (Rp)<input type="number" wire:model="cashAmount" min="1" step="1" required class="w-full rounded-lg border p-2 dark:bg-zinc-900">@error('cashAmount')<span class="text-red-600">{{ $message }}</span>@enderror</label>
                    <label>No. bukti<input wire:model="cashReference" maxlength="100" class="w-full rounded-lg border p-2 dark:bg-zinc-900"></label>
                    <label>Kegiatan terkait<select wire:model="cashActivityId" class="w-full rounded-lg border p-2 dark:bg-zinc-900"><option value="">Tidak ditautkan</option>@foreach ($activities as $activity)<option value="{{ $activity->id }}">{{ $activity->title }}</option>@endforeach</select>@error('cashActivityId')<span class="text-red-600">{{ $message }}</span>@enderror</label>
                    @can('inventory.view')
                        <label>Referensi barang<select wire:model="cashInventoryItemId" class="w-full rounded-lg border p-2 dark:bg-zinc-900"><option value="">Tidak ditautkan</option>@foreach ($inventoryOptions as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select>@error('cashInventoryItemId')<span class="text-red-600">{{ $message }}</span>@enderror</label>
                    @endcan
                    <label>Catatan<input wire:model="cashNotes" maxlength="3000" class="w-full rounded-lg border p-2 dark:bg-zinc-900"></label>
                    <div class="md:col-span-3"><button class="rounded-lg bg-emerald-700 px-4 py-2 text-white">Simpan Draf</button></div>
                </form>
            @endif
            <div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr><th>Tanggal</th><th>Uraian</th><th>Jenis</th><th>Jumlah</th><th>Status</th></tr></thead><tbody>
                @forelse ($entries as $entry)
                    <tr class="border-t" wire:key="cash-{{ $entry->id }}"><td class="p-2">{{ $entry->occurred_at?->format('d/m/Y') }}</td><td class="p-2">{{ $entry->description }}</td><td class="p-2">{{ $entry->direction === 'income' ? 'Masuk' : 'Keluar' }}</td><td class="p-2">Rp {{ number_format($entry->amount, 0, ',', '.') }}</td><td class="p-2">{{ $entry->status === 'posted' ? 'Tercatat' : 'Draf' }}
                        @if ($entry->status === 'draft' && (auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.finance.manage')))
                            <button type="button" wire:click="postCash({{ $entry->id }})" wire:confirm="Posting transaksi ini?" class="text-emerald-700 underline">Posting</button>
                        @endif
                    </td></tr>
                @empty<tr><td colspan="5" class="p-3">Belum ada transaksi.</td></tr>@endforelse
            </tbody></table></div>
            @can('reports.export')<button wire:click="publishCashBook" wire:loading.attr="disabled" class="mt-4 rounded-lg bg-emerald-700 px-4 py-2 text-white">Terbitkan Buku Kas PDF</button>@endcan
        </section>
        <section class="rounded-xl border p-5">
            <h2 class="text-lg font-semibold">3. Sarana Milik Gudep</h2>
            <p class="text-sm text-zinc-500">Menggunakan owner Gudep di Inventaris; tidak membuat stok atau transaksi peminjaman baru.</p>
            @can('inventory.view')
                <p class="mt-2 text-sm">Aset ditampilkan: {{ $assets->count() }} · Stok ditampilkan: {{ $stocks->count() }} · Peminjaman terkait: {{ $loanCount }}</p>
                <a href="{{ route('inventory.index') }}" wire:navigate class="mt-2 inline-block text-emerald-700 underline">Kelola Inventaris</a>
                <a href="{{ route('inventory.loans.index') }}" wire:navigate class="ml-3 text-emerald-700 underline">Peminjaman</a>
                <div class="mt-3 grid gap-4 md:grid-cols-2">
                    <div><h3 class="font-semibold">Aset</h3><ul>@forelse ($assets as $asset)<li>{{ $asset->item?->name ?? '-' }} — {{ $asset->asset_code }}</li>@empty<li>Belum ada aset.</li>@endforelse</ul></div>
                    <div><h3 class="font-semibold">Stok</h3><ul>@forelse ($stocks as $stock)<li>{{ $stock->item?->name ?? '-' }}: {{ $stock->quantity_on_hand }}</li>@empty<li>Belum ada stok.</li>@endforelse</ul></div>
                </div>
            @else
                <p class="mt-3 text-sm">Melihat daftar sarana memerlukan izin Inventaris.</p>
            @endcan
        </section>
    @else
        <p>Pilih Gudep dan tahun ajaran untuk melihat data.</p>
    @endif
</div>
