<div class="my-6 space-y-6">
    <flux:separator />
    <div>
        <flux:heading size="lg">Data Diri Kepala Sekolah</flux:heading>
        <flux:text>Lengkapi kontak dan identitas penandatangan Anda. Nama dan email dapat diubah pada formulir profil di atas.</flux:text>
    </div>

    @if ($schoolId)
        <form wire:submit="save" class="space-y-6">
            <flux:input label="Sekolah aktif" :value="$schoolName" readonly />
            <flux:input wire:model="phone" label="Nomor HP / WhatsApp" type="tel" autocomplete="tel" placeholder="Contoh: 081234567890" />
            <flux:input wire:model="position" label="Jabatan penandatangan" required maxlength="150" />
            <flux:select wire:model="identifierType" label="Jenis identitas">
                <flux:select.option value="NIP">NIP</flux:select.option>
                <flux:select.option value="NTA">NTA</flux:select.option>
            </flux:select>
            <flux:input wire:model="identifierNumber" label="Nomor NIP / NTA" maxlength="100" description="Boleh dikosongkan jika belum memiliki nomor identitas." />
            <flux:text>Jabatan dan identitas berlaku untuk sekolah aktif dan digunakan pada dokumen baru yang memilih akun Anda sebagai penandatangan.</flux:text>
            <flux:button type="submit" variant="primary">Simpan Data Diri</flux:button>
        </form>
    @else
        <flux:callout variant="warning">Akun belum memiliki sekolah aktif. Hubungi admin sekolah untuk mengaktifkan keanggotaan Anda.</flux:callout>
    @endif
</div>
