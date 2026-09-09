<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('code', 10);
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['school_id', 'code'], 'letter_types_school_code_unique');
        });

        Schema::create('letter_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('code', 10);
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['school_id', 'code'], 'letter_fields_school_code_unique');
        });

        Schema::create('school_letter_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('gudep_code', 80)->nullable();
            $table->string('number_format', 255)->default('{sequence}/{type}/{month_roman}.{year_short}/{gudep}-{field}');
            $table->string('agenda_format', 120)->default('{sequence_padded}/SM/{year}');
            $table->string('year_format', 20)->default('short');
            $table->string('sequence_reset', 20)->default('yearly');
            $table->string('letterhead_title', 255)->nullable();
            $table->string('letterhead_subtitle', 255)->nullable();
            $table->text('letterhead_address')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('default_signatory_name', 160)->nullable();
            $table->string('default_signatory_position', 160)->nullable();
            $table->string('default_signatory_identity', 160)->nullable();
            $table->timestamps();
        });

        Schema::create('letter_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('letter_type_id')->nullable()->constrained('letter_types')->nullOnDelete();
            $table->string('slug', 120);
            $table->string('name', 160);
            $table->string('document_kind', 40)->default('letter');
            $table->string('title', 180)->nullable();
            $table->longText('body_template');
            $table->string('default_field_code', 10)->nullable();
            $table->boolean('requires_recipient')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['school_id', 'slug'], 'letter_templates_school_slug_unique');
        });

        Schema::table('letters', function (Blueprint $table) {
            $table->foreignId('letter_type_id')->nullable()->after('letter_number')->constrained('letter_types')->nullOnDelete();
            $table->foreignId('letter_field_id')->nullable()->after('letter_type_id')->constrained('letter_fields')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->after('letter_field_id')->constrained('letter_templates')->nullOnDelete();
            $table->string('security_classification', 20)->nullable()->after('classification');
            $table->string('archive_code', 40)->nullable()->after('security_classification');
            $table->string('archive_category', 120)->nullable()->after('archive_code');
            $table->unsignedSmallInteger('retention_years')->nullable()->after('archive_category');
            $table->string('archive_status', 30)->default('active')->after('retention_years');
            $table->json('metadata')->nullable()->after('body');
            $table->string('signatory_identity', 160)->nullable()->after('signatory_position');
            $table->foreignId('published_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->index(['school_id', 'archive_code'], 'letters_school_archive_code_idx');
            $table->index(['school_id', 'security_classification'], 'letters_school_security_idx');
        });

        Schema::create('letter_dispositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('instruction');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('open');
            $table->timestamp('disposed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_dispositions');
        Schema::table('letters', function (Blueprint $table) {
            $table->dropForeign(['letter_type_id']);
            $table->dropForeign(['letter_field_id']);
            $table->dropForeign(['template_id']);
            $table->dropForeign(['published_by']);
            $table->dropIndex('letters_school_archive_code_idx');
            $table->dropIndex('letters_school_security_idx');
            $table->dropColumn([
                'letter_type_id', 'letter_field_id', 'template_id', 'security_classification', 'archive_code',
                'archive_category', 'retention_years', 'archive_status', 'metadata', 'signatory_identity', 'published_by',
            ]);
        });
        Schema::dropIfExists('letter_templates');
        Schema::dropIfExists('school_letter_settings');
        Schema::dropIfExists('letter_fields');
        Schema::dropIfExists('letter_types');
    }
};
