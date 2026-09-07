<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <flux:heading size="xl">Integrasi Pesan</flux:heading>
        <flux:text>Konfigurasi pengiriman untuk seluruh sekolah. Token dan password disimpan terenkripsi; kosongkan kolom rahasia untuk mempertahankan nilai tersimpan.</flux:text>
    </div>
    @if (session('success'))<flux:callout variant="success">{{ session('success') }}</flux:callout>@endif
    <form wire:submit="save" class="space-y-6">
        <div class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <flux:heading>WhatsApp — Fonnte</flux:heading>
            <flux:switch wire:model="whatsappEnabled" label="Aktifkan pengiriman WhatsApp" />
            <flux:input wire:model="fonnteToken" type="password" label="Token perangkat Fonnte" autocomplete="new-password" />
            <flux:text>Salin token perangkat dari dashboard Fonnte. Hubungkan nomor pengirim dengan QR di Fonnte sekali dan pastikan perangkat terhubung serta kuota tersedia.</flux:text>
        </div>
        <div class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <flux:heading>Telegram Bot</flux:heading>
            <flux:switch wire:model="telegramEnabled" label="Aktifkan pengiriman Telegram" />
            <flux:input wire:model="telegramToken" type="password" label="Token bot dari BotFather" autocomplete="new-password" />
            <flux:input wire:model="telegramUsername" label="Username bot (tanpa @)" />
            <flux:text>Penerima menghubungkan akun melalui Pengaturan Notifikasi dan menekan Start sekali. Setelah terhubung, pesan dikirim dari web tanpa membuka aplikasi. Username penerima hanya bisa digunakan jika sudah terhubung di sekolah aktif.</flux:text>
            <flux:text>Jalankan penerima update pada server: php artisan telegram:poll. Jangan gunakan polling bersamaan dengan webhook Telegram.</flux:text>
        </div>
        <div class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <flux:heading>Email SMTP</flux:heading>
            <flux:switch wire:model="emailEnabled" label="Aktifkan konfigurasi SMTP ini" />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="host" label="Host SMTP" placeholder="smtp.example.com" />
                <flux:input wire:model="port" type="number" label="Port" />
                <flux:select wire:model="scheme" label="Keamanan">
                    <flux:select.option value="smtp">STARTTLS (biasanya 587)</flux:select.option>
                    <flux:select.option value="smtps">TLS (biasanya 465)</flux:select.option>
                </flux:select>
                <flux:input wire:model="username" label="Username SMTP" />
                <flux:input wire:model="password" type="password" label="Password / App Password" autocomplete="new-password" />
                <flux:input wire:model="fromAddress" type="email" label="Email pengirim" />
                <flux:input wire:model="fromName" label="Nama pengirim" />
            </div>
            <flux:text>Digunakan untuk pengumuman dan email pengaturan password. Menonaktifkan email di sini menghentikan pengiriman email.</flux:text>
        </div>
        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">Simpan konfigurasi</flux:button>
    </form>
    <form wire:submit="sendTest" class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <flux:heading>Uji kirim</flux:heading>
        <flux:text>Simpan konfigurasi terlebih dahulu. Uji ini benar-benar mengirim pesan ke tujuan yang Anda masukkan.</flux:text>
        <flux:select wire:model="testChannel" label="Saluran">
            <flux:select.option value="whatsapp">WhatsApp</flux:select.option>
            <flux:select.option value="telegram">Telegram</flux:select.option>
            <flux:select.option value="email">Email</flux:select.option>
        </flux:select>
        <flux:input wire:model="testDestination" label="Nomor HP, username / chat ID Telegram, atau email" />
        <flux:button type="submit" wire:loading.attr="disabled">Kirim pesan uji</flux:button>
    </form>
    <flux:text>Pengumuman diproses melalui antrean: jalankan php artisan queue:work pada server. Status diterima layanan belum berarti pesan sudah dibaca penerima.</flux:text>
</div>
