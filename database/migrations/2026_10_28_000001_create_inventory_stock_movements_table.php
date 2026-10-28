<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('inventory_owners')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('inventory_locations')->restrictOnDelete();
            $table->string('movement_type', 20); // receipt | issue | adjustment
            $table->decimal('quantity', 14, 3);
            $table->decimal('balance_after', 14, 3);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['school_id', 'item_id', 'occurred_at']);
            $table->index(['school_id', 'owner_id', 'location_id']);
            $table->index(['school_id', 'movement_type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_movements');
    }
};
