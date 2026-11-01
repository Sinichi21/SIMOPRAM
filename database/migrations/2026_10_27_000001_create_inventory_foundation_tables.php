<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'name']);
            $table->index(['school_id', 'is_active']);
        });

        Schema::create('inventory_owners', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('owner_type', 20); // school | gudep
            $table->foreignId('scout_group_id')->nullable()->constrained('scout_groups')->restrictOnDelete();
            $table->string('display_name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'owner_type', 'is_active']);
            $table->index(['school_id', 'scout_group_id']);
        });

        Schema::create('inventory_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scout_group_id')->nullable()->constrained('scout_groups')->restrictOnDelete();
            $table->string('name', 120);
            $table->string('location_type', 30)->default('storage');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'name']);
            $table->index(['school_id', 'scout_group_id', 'is_active']);
        });

        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained('inventory_categories')->restrictOnDelete();
            $table->string('item_code', 60);
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->string('tracking_type', 20); // quantity | individual
            $table->string('unit', 30)->default('unit');
            $table->decimal('minimum_stock', 14, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'item_code']);
            $table->index(['school_id', 'category_id', 'is_active']);
            $table->index(['school_id', 'name']);
        });

        Schema::create('inventory_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('inventory_owners')->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('inventory_locations')->restrictOnDelete();
            $table->string('asset_code', 80);
            $table->string('serial_number', 120)->nullable();
            $table->string('condition', 30)->default('good');
            $table->string('status', 30)->default('available');
            $table->date('purchased_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'asset_code']);
            $table->index(['school_id', 'item_id', 'status']);
            $table->index(['school_id', 'owner_id', 'location_id']);
        });

        Schema::create('inventory_stock_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('inventory_owners')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('inventory_locations')->restrictOnDelete();
            $table->decimal('quantity_on_hand', 14, 3)->default(0);
            $table->decimal('quantity_reserved', 14, 3)->default(0);
            $table->timestamps();

            $table->unique(
                ['school_id', 'item_id', 'owner_id', 'location_id'],
                'inv_stock_balance_unique'
            );
            $table->index(['school_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_balances');
        Schema::dropIfExists('inventory_assets');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_locations');
        Schema::dropIfExists('inventory_owners');
        Schema::dropIfExists('inventory_categories');
    }
};
