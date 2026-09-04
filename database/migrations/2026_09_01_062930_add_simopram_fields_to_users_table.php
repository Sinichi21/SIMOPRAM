<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 100)
                ->nullable()
                ->unique()
                ->after('name');

            $table->string('phone', 30)
                ->nullable()
                ->after('email');

            $table->string('avatar')
                ->nullable();

            $table->string('system_role', 30)
                ->nullable()
                ->index();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamp('last_login_at')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'phone',
                'avatar',
                'system_role',
                'is_active',
                'last_login_at',
            ]);
        });
    }
};
