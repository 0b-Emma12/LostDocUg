<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('claimant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->enum('status', ['pending', 'reviewing', 'approved', 'rejected', 'withdrawn', 'expired'])->default('pending');
            $table->text('encrypted_evidence')->nullable();
            $table->text('claimant_statement')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision_reason', 100)->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->string('recovery_method', 50)->nullable();
            $table->timestamps();

            $table->index(['match_candidate_id', 'status']);
            $table->index(['claimant_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
