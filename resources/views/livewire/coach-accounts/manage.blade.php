<div class="space-y-6">
    @if (session('success'))<flux:callout variant="success">{{ session('success') }}</flux:callout>@endif
    <div><flux:heading size="xl">Akun Pembina</flux:heading><flux:text>{{ $coach->name }}</flux:text></div>
    <flux:card class="max-w-xl">
        @if (! $coach->user)
            <form wire:submit="createAccount" class="space-y-4">
                <flux:heading>Buat Akun Login</flux:heading>
                <flux:input wire:model="email" label="Email" type="email" required />
                <flux:input wire:model="password" label="Password" type="password" required viewable />
                <flux:input wire:model="password_confirmation" label="Konfirmasi Password" type="password" required viewable />
                <flux:button type="submit" variant="primary">Buat Akun</flux:button>
            </form>
        @else
            <flux:text>Akun terhubung: <strong>{{ $coach->user->email }}</strong></flux:text>
            <form wire:submit="resetPassword" class="mt-6 space-y-4">
                <flux:heading>Ubah Password</flux:heading>
                <flux:input wire:model="password" label="Password Baru" type="password" required viewable />
                <flux:input wire:model="password_confirmation" label="Konfirmasi Password" type="password" required viewable />
                <flux:button type="submit" variant="primary">Ubah Password</flux:button>
            </form>
        @endif
    </flux:card>
</div>
