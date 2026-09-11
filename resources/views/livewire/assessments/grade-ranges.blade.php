<section
    class="rounded-xl border border-zinc-200
           bg-white p-6 shadow-sm
           dark:border-zinc-800
           dark:bg-zinc-900"
>
    <h2 class="text-lg font-semibold">
        Rentang Predikat dan Saran Deskripsi
    </h2>
    <p class="mt-1 text-sm text-zinc-500">
        Atur rentang nilai 0-100 hingga dua angka desimal.
        Deskripsi menjadi saran sesuai predikat dan dapat
        disesuaikan untuk setiap siswa di menu Penilaian.
    </p>

    @if (session('grade-ranges'))
        <flux:callout class="mt-4">{{ session('grade-ranges') }}</flux:callout>
    @endif

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:error name="ranges" />
        @foreach ($ranges as $index => $range)
            <fieldset
                wire:key="grade-range-{{ $index }}"
                class="min-w-0 space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700"
            >
                <legend class="px-2 text-sm font-semibold">Rentang {{ $index + 1 }}</legend>

                <div class="grid gap-4 md:grid-cols-3">
                    <flux:input wire:model="ranges.{{ $index }}.letter_grade" label="Predikat" />
                    <flux:input wire:model="ranges.{{ $index }}.min_score" label="Nilai minimum" type="number" step="0.01" />
                    <flux:input wire:model="ranges.{{ $index }}.max_score" label="Nilai maksimum" type="number" step="0.01" />
                </div>

                <flux:textarea wire:model="ranges.{{ $index }}.description" label="Saran deskripsi" rows="2" />

                <div class="flex justify-end">
                    <flux:button type="button" wire:click="removeRange({{ $index }})">Hapus rentang</flux:button>
                </div>
            </fieldset>
        @endforeach
        <div class="flex flex-col gap-2 border-t border-zinc-200 pt-4 sm:flex-row sm:flex-wrap dark:border-zinc-800">
            <flux:button type="button" wire:click="addRange">Tambah rentang</flux:button>
            <flux:button type="submit" variant="primary">Simpan Rentang dan Deskripsi</flux:button>
        </div>
    </form>
</section>
