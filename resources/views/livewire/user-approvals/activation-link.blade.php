<div class="space-y-3">
    <flux:error name="activation" />
    <div class="flex flex-wrap gap-2">
        <flux:button wire:click="sendLink({{ $linkAction }}'email')" wire:loading.attr="disabled">Kirim Tautan via Email</flux:button>
        <flux:button wire:click="sendLink({{ $linkAction }}'share')" wire:loading.attr="disabled">Siapkan Tautan WhatsApp / Telegram</flux:button>
    </div>
    @if ($activationLink)
        <flux:input label="Tautan pengaturan password" value="{{ $activationLink }}" readonly />
        <flux:text>Tautan berlaku {{ config('auth.passwords.users.expire') }} menit dan hanya dapat digunakan sekali. Kirim hanya kepada pemilik akun.</flux:text>
        <div class="flex flex-wrap gap-2">
            <flux:button href="{{ 'https://wa.me/?text='.rawurlencode('Silakan atur password akun Anda: '.$activationLink) }}" target="_blank" rel="noopener noreferrer">Bagikan ke WhatsApp</flux:button>
            <flux:button href="{{ 'https://t.me/share/url?url='.rawurlencode($activationLink).'&text='.rawurlencode('Silakan atur password akun Anda.') }}" target="_blank" rel="noopener noreferrer">Bagikan ke Telegram</flux:button>
        </div>
    @endif
</div>
