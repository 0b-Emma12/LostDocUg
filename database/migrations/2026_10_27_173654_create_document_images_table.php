<?php

use App\Enums\ImageScanStatus;
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
        Schema::create('document_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->restrictOnDelete();
            $table->string('storage_key')->unique();
            $table->string('content_hash', 64);
            $table->string('mime_type', 50);
            $table->unsignedInteger('size_bytes');
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->enum('scan_status', array_column(ImageScanStatus::cases(), 'value'))->default(ImageScanStatus::Pending->value);
            $table->timestamp('retention_deadline')->nullable();
            $table->timestamps();
            $table->unique(['report_id', 'content_hash']);
            $table->index(['report_id', 'scan_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_images');
    }
};
