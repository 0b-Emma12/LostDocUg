<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_candidates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lost_report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('found_report_id')->constrained('reports')->cascadeOnDelete();
            $table->unsignedSmallInteger('score');
            $table->json('reason_codes')->nullable();
            $table->json('field_agreements')->nullable();
            $table->string('scoring_version', 20)->default('1.0');
            $table->enum('status', ['pending', 'under_review', 'notified', 'claimed', 'confirmed', 'dismissed', 'expired'])->default('pending');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['lost_report_id', 'found_report_id']);
            $table->index(['lost_report_id', 'status']);
            $table->index(['found_report_id', 'status']);
            $table->index(['status', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_candidates');
    }
};
