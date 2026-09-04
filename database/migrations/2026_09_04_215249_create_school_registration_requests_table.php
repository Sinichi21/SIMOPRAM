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
        Schema::create('school_registration_requests', function (Blueprint $table) {
            $table->id();
            $table->string('school_name', 200);
            $table->string('npsn', 20)->unique();
            $table->string('level', 30);
            $table->string('city', 100);
            $table->string('contact_name', 150);
            $table->string('contact_phone', 30);
            $table->string('contact_email', 150);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_registration_requests');
    }
};
