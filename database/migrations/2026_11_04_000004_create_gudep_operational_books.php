<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudep_guest_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->dateTime('visited_at');
            $table->string('guest_name', 150);
            $table->string('organization', 180)->nullable();
            $table->string('purpose', 255);
            $table->string('received_by', 150)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['school_id', 'scout_group_id', 'academic_year_id', 'visited_at'], 'gudep_guests_scope_idx');
        });

        Schema::create('gudep_meetings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->string('title', 200);
            $table->dateTime('held_at');
            $table->string('location', 200)->nullable();
            $table->string('chairperson', 150)->nullable();
            $table->string('minute_taker', 150)->nullable();
            $table->text('attendees')->nullable();
            $table->text('agenda')->nullable();
            $table->longText('minutes')->nullable();
            $table->string('status', 20)->default('draft');
            $table->dateTime('finalized_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['school_id', 'scout_group_id', 'academic_year_id', 'held_at'], 'gudep_meetings_scope_idx');
        });

        Schema::create('gudep_meeting_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->constrained('scout_groups')->restrictOnDelete();
            $table->foreignId('gudep_meeting_id')->constrained('gudep_meetings')->restrictOnDelete();
            $table->text('decision');
            $table->string('person_responsible', 150)->nullable();
            $table->date('due_at')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamps();
            $table->index(['school_id', 'scout_group_id', 'gudep_meeting_id'], 'gudep_meeting_decisions_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudep_meeting_decisions');
        Schema::dropIfExists('gudep_meetings');
        Schema::dropIfExists('gudep_guest_entries');
    }
};
