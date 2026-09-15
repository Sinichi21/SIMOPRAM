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
        Schema::table('activities', function (Blueprint $table): void {
            $table->foreignId('organizer_school_id')->nullable()->constrained('schools')->restrictOnDelete();
            $table->string('approval_status', 20)->default('approved')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason', 1000)->nullable();
            $table->boolean('registration_open')->default(false);
            $table->json('registration_fields')->nullable();
            $table->json('registration_categories')->nullable();
            $table->text('registration_terms')->nullable();
            $table->unsignedSmallInteger('team_min')->default(1);
            $table->unsignedSmallInteger('team_max')->default(20);
            $table->string('banner_path')->nullable();
            $table->json('attachments')->nullable();
        });
        Schema::table('announcements', fn (Blueprint $table) => $table->json('attachments')->nullable());
        Schema::create('activity_delegates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('requesting_school_id')->nullable()->constrained('schools')->restrictOnDelete();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 1000)->nullable();
            $table->timestamps();
            $table->unique(['activity_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
        Schema::create('activity_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 150);
            $table->string('category', 20);
            $table->string('status', 20)->default('active');
            $table->string('validation_status', 20)->default('pending');
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->json('answers')->nullable();
            $table->json('form_snapshot');
            $table->text('terms_snapshot');
            $table->timestamp('declaration_accepted_at');
            $table->timestamp('terms_accepted_at');
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->index(['activity_id', 'status', 'validation_status']);
        });
        Schema::create('activity_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entry_id')->constrained('activity_entries')->cascadeOnDelete();
            $table->boolean('is_reserve')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('origin_school_id')->nullable()->constrained('schools')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('identifier', 50)->nullable();
            $table->string('school_name', 200)->nullable();
            $table->string('role', 20);
            $table->string('channel', 20);
            $table->text('destination');
            $table->char('identity_key', 64);
            $table->string('status', 20)->default('active');
            $table->char('token_hash', 64)->nullable()->unique();
            $table->unsignedInteger('access_version')->default(1);
            $table->string('delivery_status', 20)->default('pending');
            $table->timestamp('link_requested_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();
            $table->unique(['entry_id', 'identity_key']);
            $table->index(['activity_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_registrations');
        Schema::dropIfExists('activity_entries');
        Schema::dropIfExists('activity_delegates');
        Schema::table('activities', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organizer_school_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['approval_status', 'reviewed_at', 'rejection_reason', 'registration_open', 'registration_fields', 'registration_categories', 'registration_terms', 'team_min', 'team_max', 'banner_path', 'attachments']);
        });
        Schema::table('announcements', fn (Blueprint $table) => $table->dropColumn('attachments'));
    }
};
