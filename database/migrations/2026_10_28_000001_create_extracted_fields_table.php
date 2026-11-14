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
        Schema::create('extracted_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_image_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->text('encrypted_payload')->default('');
            $table->unsignedSmallInteger('key_version')->default(1);
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->string('document_type', 50);
            $table->string('engine', 50)->default('paddleocr');
            $table->string('parser_version', 20)->default('1');
            $table->boolean('review_required')->default(false);
            $table->string('review_reason', 100)->nullable();
            $table->decimal('overall_confidence', 5, 4)->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'needs_review'])->default('pending');
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['report_id', 'status']);
            $table->index(['document_image_id']);
            $table->index(['status', 'review_required']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extracted_fields');
    }
};
