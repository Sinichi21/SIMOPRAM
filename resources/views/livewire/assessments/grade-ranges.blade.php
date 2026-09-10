<div>
    <flux:heading size="lg">Rentang Predikat dan Saran Deskripsi</flux:heading>
    <flux:text>Atur rentang nilai 0–100 hingga dua angka desimal. Deskripsi menjadi saran sesuai predikat dan dapat disesuaikan untuk setiap siswa di menu Penilaian.</flux:text>
    @if (session('grade-ranges')) <flux:callout>{{ session('grade-ranges') }}</flux:callout> @endif
    <form wire:submit="save">
        <flux:error name="ranges" />
        @foreach ($ranges as $index => $range)
            <fieldset wire:key="grade-range-{{ $index }}">
                <flux:input wire:model="ranges.{{ $index }}.letter_grade" label="Predikat" />
                <flux:input wire:model="ranges.{{ $index }}.min_score" label="Nilai minimum" type="number" step="0.01" />
                <flux:input wire:model="ranges.{{ $index }}.max_score" label="Nilai maksimum" type="number" step="0.01" />
                <flux:textarea wire:model="ranges.{{ $index }}.description" label="Saran deskripsi" />
                <flux:button type="button" wire:click="removeRange({{ $index }})">Hapus rentang</flux:button>
            </fieldset>
        @endforeach
        <flux:button type="button" wire:click="addRange">Tambah rentang</flux:button>
        <flux:button type="submit" variant="primary">Simpan Rentang dan Deskripsi</flux:button>
    </form>
</div>
