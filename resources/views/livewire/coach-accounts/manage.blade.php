<div class="space-y-6">
    @if (session('success'))<flux:callout variant="success">{{ session('success') }}</flux:callout>@endif
    <div><flux:heading size="xl">Akun Pembina</flux:heading><flux:text>{{ $coach->name }}</flux:text></div>
    <flux:card class="max-w-xl space-y-4">
        @if (! $coach->user)
            <form wire:submit="createAccount" class="space-y-4">
                <flux:heading>Hubungkan Akun</flux:heading>
                <flux:text>Gunakan email akun yang sama jika pengguna sudah terdaftar di sekolah lain. Pengguna baru mengatur password sendiri melalui tautan aktivasi.</flux:text>
                <flux:input wire:model="email" label="Email pengguna" type="email" required />
                <flux:button type="submit" variant="primary">Hubungkan Akun</flux:button>
            </form>
        @else
            <flux:text>Akun terhubung: <strong>{{ $coach->user->email }}</strong></flux:text>
            <flux:text>{{ $coach->user->activation_pending ? 'Menunggu pengguna mengatur password untuk aktivasi.' : ($coach->user->is_active ? 'Akun aktif.' : 'Akun dinonaktifkan.') }}</flux:text>
            @include('livewire.user-approvals.activation-link', ['linkAction' => '', 'activationLink' => $activationLink])
        @endif
    </flux:card>
</div>
