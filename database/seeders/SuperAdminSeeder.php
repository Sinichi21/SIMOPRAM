<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            [
                'email' => env(
                    'SUPER_ADMIN_EMAIL',
                    'admin@simpram.local'
                ),
            ],
            [
                'name' => env(
                    'SUPER_ADMIN_NAME',
                    'Super Admin'
                ),

                'username' => env(
                    'SUPER_ADMIN_USERNAME',
                    'superadmin'
                ),

                'password' => Hash::make(
                    env(
                        'SUPER_ADMIN_PASSWORD',
                        'ChangeMe123!'
                    )
                ),

                'system_role' => 'super_admin',

                'is_active' => true,
            ]
        );
    }
}
