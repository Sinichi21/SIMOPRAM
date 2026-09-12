<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'occurred_at' => now(), 'created_at' => now(), 'log_type' => 'audit',
            'user_id' => null, 'user_name' => 'Pengguna Audit', 'role' => 'coach',
            'module' => 'grades', 'action' => 'updated', 'target_type' => 'Student', 'target_id' => '215',
            'description' => 'Mengubah nilai siswa', 'old_values' => ['score' => 78], 'new_values' => ['score' => 88],
            'ip_address' => '127.0.0.1', 'user_agent' => 'Chrome / Windows',
            'status' => 'success', 'request_id' => 'req_'.fake()->uuid(),
        ];
    }
}
