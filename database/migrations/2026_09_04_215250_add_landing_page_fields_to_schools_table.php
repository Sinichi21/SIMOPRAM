<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('tagline', 180)->nullable()->after('logo');
            $table->text('profile')->nullable()->after('tagline');
            $table->string('primary_color', 7)->default('#166534')->after('profile');
            $table->string('hero_image')->nullable()->after('primary_color');
            $table->boolean('registration_open')->default(true)->after('hero_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'profile', 'primary_color', 'hero_image', 'registration_open']);
        });
    }
};
