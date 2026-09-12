<?php

use App\Livewire\ActivityLogs\Index;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('super admin can browse global logs and inspect details', function () {
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $log = ActivityLog::factory()->create(['description' => 'Mengubah nilai siswa B']);

    $this->actingAs($admin)->get(route('activity-logs.index'))->assertOk()->assertSee('Log Aktivitas');
    Livewire::test(Index::class)->call('show', $log->id)->assertSee($log->request_id)
        ->assertSee('dari 78 menjadi 88')->assertSee('Chrome / Windows');
});

test('non super admins are denied the page details and PDF', function (string $role) {
    $user = User::factory()->create(['system_role' => $role]);
    $this->actingAs($user);

    $this->get(route('activity-logs.index'))->assertForbidden();
    $this->get(route('activity-logs.export', ['from' => '2026-09-01', 'to' => '2026-09-12']))->assertForbidden();
    Livewire::test(Index::class)->assertForbidden();
})->with(['school_admin', 'scout_admin', 'coach', 'student', 'principal']);

test('guests cannot browse or export activity logs', function () {
    $this->get(route('activity-logs.index'))->assertRedirect(route('login'));
    $this->get(route('activity-logs.export'))->assertRedirect(route('login'));
});

test('every audit filter restricts the result and includes the complete final day in WITA', function () {
    $matching = ActivityLog::factory()->create([
        'occurred_at' => '2026-09-12 23:59:59', 'school_id' => 4, 'user_id' => 51,
        'user_name' => 'Pembina Contoh', 'role' => 'coach', 'module' => 'grades',
        'action' => 'updated', 'status' => 'success', 'log_type' => 'audit',
    ]);
    $filters = ['from' => '2026-09-12', 'to' => '2026-09-12', 'school' => '4', 'user' => '51', 'role' => 'coach', 'module' => 'grades', 'action' => 'updated', 'status' => 'success', 'type' => 'audit'];
    foreach (['occurred_at' => '2026-09-13 00:00:00', 'school_id' => 5, 'user_id' => 52, 'role' => 'student', 'module' => 'users', 'action' => 'created', 'status' => 'failed', 'log_type' => 'system'] as $field => $value) {
        ActivityLog::factory()->create([...$matching->getAttributes(), 'id' => null, 'request_id' => 'req_'.Str::uuid(), $field => $value]);
    }

    expect(app(ActivityLogQuery::class)->build($filters)->pluck('id')->all())->toBe([$matching->id]);
    expect(app(ActivityLogQuery::class)->build([...$filters, 'user' => 'Pembina Contoh', 'request_id' => $matching->request_id])->pluck('id')->all())->toBe([$matching->id]);
});

test('PDF exports all matching rows rather than only the current page', function () {
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    ActivityLog::factory()->count(26)->create(['occurred_at' => '2026-09-12 12:00:00', 'module' => 'grades']);
    ActivityLog::factory()->create(['occurred_at' => '2026-09-11 12:00:00']);
    $pdf = Mockery::mock(Barryvdh\DomPDF\PDF::class);
    Pdf::shouldReceive('loadView')->once()->with('exports.activity-logs', Mockery::on(fn (array $data): bool => $data['logs']->count() === 26))->andReturn($pdf);
    $pdf->shouldReceive('setPaper')->with('a4', 'landscape')->andReturnSelf();
    $pdf->shouldReceive('setOption')->with('isRemoteEnabled', false)->andReturnSelf();
    $pdf->shouldReceive('download')->once()->andReturn(response('%PDF-test', 200, ['Content-Type' => 'application/pdf']));

    $this->actingAs($admin)->get(route('activity-logs.export', ['from' => '2026-09-12', 'to' => '2026-09-12', 'module' => 'grades']))->assertOk()->assertHeader('Content-Type', 'application/pdf');

    $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'action' => 'exported', 'module' => 'reports']);
});

test('PDF renders a real document and escapes text', function () {
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $log = ActivityLog::factory()->create(['user_name' => '<script>alert(1)</script>']);
    $filters = ['from' => now()->toDateString(), 'to' => now()->toDateString()];

    $response = $this->actingAs($admin)->get(route('activity-logs.export', $filters))->assertOk()->assertHeader('Content-Type', 'application/pdf');

    expect($response->getContent())->toStartWith('%PDF');
    $html = view('exports.activity-logs', ['logs' => collect([$log]), 'filters' => $filters, 'exportedBy' => $admin->name])->render();
    expect($html)->toContain('&lt;script&gt;')->not->toContain('<script>alert(1)</script>');
});

test('invalid export ranges are rejected on the server', function (array $filters, string $error) {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
        ->get(route('activity-logs.export', $filters))->assertSessionHasErrors($error);
})->with([
    'missing dates' => [[], 'from'],
    'reversed range' => [['from' => '2026-09-12', 'to' => '2026-09-01'], 'to'],
    'invalid module' => [['from' => '2026-09-01', 'to' => '2026-09-12', 'module' => 'malicious'], 'module'],
]);

test('oversized exports require a narrower range without silent truncation', function () {
    config(['activity-log.pdf_limit' => 2]);
    ActivityLog::factory()->count(3)->create(['module' => 'grades']);

    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))
        ->get(route('activity-logs.export', ['from' => now()->toDateString(), 'to' => now()->toDateString(), 'module' => 'grades']))
        ->assertSessionHasErrors('export');
});

test('detail access is checked again after super admin privileges are revoked', function () {
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $log = ActivityLog::factory()->create();
    $component = Livewire::actingAs($admin)->test(Index::class);
    $admin->update(['system_role' => 'student']);

    $component->call('show', $log->id)->assertForbidden();
});
