<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Log Aktivitas SIMPRAM</title>
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #18181b; }
        h1 { font-size: 17px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; table-layout: fixed; }
        th, td { border: 1px solid #d4d4d8; padding: 5px; text-align: left; vertical-align: top; overflow-wrap: break-word; }
        th { background: #f4f4f5; }
        .entry { page-break-inside: avoid; margin: 12px 0; border-top: 1px solid #a1a1aa; padding-top: 8px; }
        pre { white-space: pre-wrap; overflow-wrap: break-word; font-size: 8px; }
        .muted { color: #52525b; }
    </style>
</head>
<body>
    <h1>Log Aktivitas SIMPRAM</h1>
    <p>Rentang: {{ $filters['from'] }} sampai {{ $filters['to'] }} (WITA, termasuk kedua tanggal)</p>
    <p>Diekspor oleh: {{ $exportedBy }} · {{ now(config('activity-log.timezone'))->format('d/m/Y H:i:s') }} WITA · Jumlah: {{ $logs->count() }} log</p>
    <p class="muted">Filter:
        @foreach (['school' => 'School ID', 'user' => 'User', 'role' => 'Role', 'module' => 'Modul', 'action' => 'Aksi', 'status' => 'Status', 'type' => 'Kategori', 'request_id' => 'Request ID'] as $key => $label)
            {{ $label }}: {{ ($filters[$key] ?? '') !== '' ? $filters[$key] : 'Semua' }};
        @endforeach
    </p>
    @forelse ($logs as $log)
        <div class="entry">
            <strong>#{{ $log->id }} — {{ $log->summary() }}</strong>
            <table>
                <tr><th>Timestamp / Created At</th><td>{{ $log->localTimestamp() }} / {{ $log->created_at->setTimezone(config('activity-log.timezone'))->format('d/m/Y H:i:s') }} WITA</td><th>User ID / nama / role</th><td>{{ $log->user_id ?? '-' }} / {{ $log->user_name }} / {{ $log->role }}</td></tr>
                <tr><th>Sekolah</th><td>{{ $log->school_id ?? '-' }} / {{ $log->school_name ?? 'Global' }}</td><th>Modul / aksi</th><td>{{ $log->module }} / {{ $log->action }}</td></tr>
                <tr><th>Target Type / ID</th><td>{{ $log->target_type ?? '-' }} / {{ $log->target_id ?? '-' }}</td><th>Kategori / status</th><td>{{ $log->log_type }} / {{ $log->status }}</td></tr>
                <tr><th>IP / User Agent</th><td>{{ $log->ip_address ?? '-' }} / {{ $log->user_agent ?? '-' }}</td><th>Request ID</th><td>{{ $log->request_id }}</td></tr>
            </table>
            <p>{{ $log->description }}</p>
            <strong>Old Value</strong><pre>{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            <strong>New Value</strong><pre>{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @empty
        <p>Tidak ada log pada rentang dan filter ini.</p>
    @endforelse
</body>
</html>
