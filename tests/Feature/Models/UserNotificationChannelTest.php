<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('active verified Telegram channels can be counted', function () {
    $school = School::query()->create([
        'npsn' => '12345678',
        'name' => 'Sekolah Pengujian',
        'slug' => 'sekolah-pengujian',
    ]);
    $user = User::factory()->create();

    DB::table('user_notification_channels')->insert([
        'school_id' => $school->id,
        'user_id' => $user->id,
        'channel' => 'telegram',
        'destination' => '123456789',
        'is_verified' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $telegramChannels = DB::table('user_notification_channels')
        ->where('channel', 'telegram')
        ->where('is_verified', true)
        ->where('is_active', true)
        ->count();

    expect($telegramChannels)->toBe(1);
});
