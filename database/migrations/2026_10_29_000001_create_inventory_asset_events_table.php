<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_asset_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('asset_id')->constrained('inventory_assets')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->string('from_condition', 30);
            $table->string('to_condition', 30);
            $table->text('reason');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['school_id', 'asset_id', 'occurred_at'], 'inv_asset_evt_school_asset_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_asset_events');
    }
};
