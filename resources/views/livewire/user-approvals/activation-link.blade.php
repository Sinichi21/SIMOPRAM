<div class="space-y-3">
    <flux:error name="activation" />
    <flux:input wire:model="activationDestination" label="Tujuan WhatsApp / Telegram" placeholder="08…, @username, atau chat ID" />
    <flux:text>Kosongkan untuk memakai nomor akun atau Telegram yang terhubung. Email dikirim ke alamat pemilik akun.</flux:text>
    <div class="flex flex-wrap gap-2">
        <flux:button wire:click="sendLink({{ $linkAction }}'email')" wire:loading.attr="disabled">Kirim Tautan via Email</flux:button>
        <flux:button wire:click="sendLink({{ $linkAction }}'whatsapp')" wire:loading.attr="disabled">Kirim via WhatsApp</flux:button>
        <flux:button wire:click="sendLink({{ $linkAction }}'telegram')" wire:loading.attr="disabled">Kirim via Telegram</flux:button>
        <flux:button wire:click="sendLink({{ $linkAction }}'share')" wire:loading.attr="disabled">Siapkan tautan untuk disalin</flux:button>
    </div>
    @if ($activationLink)
        <flux:input label="Tautan pengaturan password" value="{{ $activationLink }}" readonly />
        <flux:text>Tautan berlaku {{ config('auth.passwords.users.expire') }} menit dan hanya dapat digunakan sekali. Kirim hanya kepada pemilik akun.</flux:text>
    @endif
</div>
