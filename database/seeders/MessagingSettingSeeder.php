<?php

namespace Database\Seeders;

use App\Models\MessagingSetting;
use Illuminate\Database\Seeder;

class MessagingSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MessagingSetting::query()->firstOrCreate(
            ['channel' => 'whatsapp'],
            ['enabled' => false, 'options' => []],
        );
    }
}
