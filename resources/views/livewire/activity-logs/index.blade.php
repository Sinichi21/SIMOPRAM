<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Log Aktivitas</flux:heading>
            <flux:text>Jejak audit, keamanan, dan kegagalan sistem. Semua waktu ditampilkan dalam WITA.</flux:text>
        </div>
        <flux:button :href="route('activity-logs.export', $filters)" variant="primary" target="_blank" rel="noopener noreferrer">Ekspor PDF</flux:button>
    </div>
    <flux:text>Ekspor mengikuti seluruh filter di bawah, maksimal {{ config('activity-log.pdf_limit') }} log per PDF. Data rahasia dan isi dokumen disembunyikan.</flux:text>
    @if ($errors->any())
        <flux:callout variant="danger">
            @foreach ($errors->all() as $error)
                <div wire:key="log-error-{{ $loop->index }}">{{ $error }}</div>
            @endforeach
        </flux:callout>
    @endif
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <flux:input type="date" label="Dari tanggal (WITA)" wire:model.live="filters.from" />
        <flux:input type="date" label="Sampai tanggal (WITA)" wire:model.live="filters.to" />
        <flux:select label="Sekolah" wire:model.live="filters.school">
            <flux:select.option value="">Semua sekolah / global</flux:select.option>
            @foreach ($schools as $school)
                <flux:select.option value="{{ $school->school_id }}" wire:key="log-school-{{ $school->school_id }}-{{ $loop->index }}">{{ $school->school_name ?? 'Sekolah' }} (#{{ $school->school_id }})</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input label="User" placeholder="ID atau nama pengguna" wire:model.live.debounce.400ms="filters.user" />
        <flux:select label="Role" wire:model.live="filters.role">
            <flux:select.option value="">Semua role</flux:select.option>
            @foreach (config('activity-log.roles') as $value => $label)
                <flux:select.option :value="$value" wire:key="log-role-{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select label="Modul" wire:model.live="filters.module">
            <flux:select.option value="">Semua modul</flux:select.option>
            @foreach (config('activity-log.modules') as $value => $label)
                <flux:select.option :value="$value" wire:key="log-module-{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select label="Aksi" wire:model.live="filters.action">
            <flux:select.option value="">Semua aksi</flux:select.option>
            @foreach (config('activity-log.actions') as $value => $label)
                <flux:select.option :value="$value" wire:key="log-action-{{ $value }}">{{ $value }} — {{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select label="Status" wire:model.live="filters.status">
            <flux:select.option value="">Semua status</flux:select.option>
            <flux:select.option value="success">Success</flux:select.option>
            <flux:select.option value="failed">Failed</flux:select.option>
        </flux:select>
        <flux:select label="Kategori" wire:model.live="filters.type">
            <flux:select.option value="">Semua kategori</flux:select.option>
            <flux:select.option value="audit">Audit</flux:select.option>
            <flux:select.option value="security">Security</flux:select.option>
            <flux:select.option value="system">System</flux:select.option>
        </flux:select>
        <flux:input label="Request ID" placeholder="req_..." wire:model.live.debounce.400ms="filters.request_id" />
    </div>
    <div class="overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Waktu / kategori</flux:table.column>
                <flux:table.column>Pengguna / role</flux:table.column>
                <flux:table.column>Sekolah</flux:table.column>
                <flux:table.column>Modul / aksi</flux:table.column>
                <flux:table.column>Aktivitas</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Detail</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($logs as $log)
                    <flux:table.row wire:key="activity-log-{{ $log->id }}">
                        <flux:table.cell>{{ $log->localTimestamp() }}<flux:text>{{ ucfirst($log->log_type) }}</flux:text></flux:table.cell>
                        <flux:table.cell>{{ $log->user_name }}<flux:text>{{ config('activity-log.roles.'.$log->role, $log->role) }} · #{{ $log->user_id ?? '-' }}</flux:text></flux:table.cell>
                        <flux:table.cell>{{ $log->school_name ?? 'Global' }}</flux:table.cell>
                        <flux:table.cell>{{ $log->module }}<flux:text>{{ $log->action }}</flux:text></flux:table.cell>
                        <flux:table.cell><div class="max-w-sm whitespace-normal">{{ $log->description }}</div></flux:table.cell>
                        <flux:table.cell><flux:badge :color="$log->status === 'success' ? 'green' : 'red'">{{ $log->status }}</flux:badge></flux:table.cell>
                        <flux:table.cell><flux:button size="sm" wire:click="show({{ $log->id }})">Detail</flux:button></flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="7">Tidak ada log untuk rentang dan filter ini.</flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
    {{ $logs->links() }}
    <flux:modal wire:model="showDetail" class="w-full md:max-w-4xl">
        @if ($selected)
            <div class="space-y-5">
                <flux:heading size="lg">Detail Log #{{ $selected->id }}</flux:heading>
                <flux:callout>{{ $selected->summary() }}</flux:callout>
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div><dt>Timestamp</dt><dd>{{ $selected->localTimestamp() }}</dd></div>
                    <div><dt>Kategori / status</dt><dd>{{ $selected->log_type }} / {{ $selected->status }}</dd></div>
                    <div><dt>User ID / nama</dt><dd>{{ $selected->user_id ?? '-' }} / {{ $selected->user_name }}</dd></div>
                    <div><dt>Role</dt><dd>{{ config('activity-log.roles.'.$selected->role, $selected->role) }} ({{ $selected->role }})</dd></div>
                    <div><dt>School ID / nama</dt><dd>{{ $selected->school_id ?? '-' }} / {{ $selected->school_name ?? 'Global' }}</dd></div>
                    <div><dt>Modul / aksi</dt><dd>{{ $selected->module }} / {{ $selected->action }}</dd></div>
                    <div><dt>Target Type / ID</dt><dd>{{ $selected->target_type ?? '-' }} / {{ $selected->target_id ?? '-' }}</dd></div>
                    <div><dt>IP Address</dt><dd>{{ $selected->ip_address ?? '-' }}</dd></div>
                    <div><dt>User Agent</dt><dd>{{ $selected->user_agent ?? '-' }}</dd></div>
                    <div><dt>Created At</dt><dd>{{ $selected->created_at->setTimezone(config('activity-log.timezone'))->format('d/m/Y H:i:s') }} WITA</dd></div>
                    <div class="sm:col-span-2"><dt>Request ID</dt><dd class="break-all">{{ $selected->request_id }}</dd></div>
                </dl>
                <div class="grid gap-4 md:grid-cols-2">
                    <div><flux:heading>Old Value</flux:heading><pre class="overflow-auto whitespace-pre-wrap break-all rounded-lg bg-zinc-100 p-3 text-xs dark:bg-zinc-800">{{ json_encode($selected->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></div>
                    <div><flux:heading>New Value</flux:heading><pre class="overflow-auto whitespace-pre-wrap break-all rounded-lg bg-zinc-100 p-3 text-xs dark:bg-zinc-800">{{ json_encode($selected->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></div>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
