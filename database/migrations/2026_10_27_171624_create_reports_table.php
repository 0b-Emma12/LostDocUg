<?php

use App\Enums\DocumentKind;
use App\Enums\ReportKind;
use App\Enums\ReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('public_reference')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('kind', array_column(ReportKind::cases(), 'value'));
            $table->enum('document_type', array_column(DocumentKind::cases(), 'value'));
            $table->enum('status', array_column(ReportStatus::cases(), 'value'))->default(ReportStatus::Processing->value);
            $table->date('incident_date');
            $table->string('district')->nullable();
            $table->text('location_description')->nullable();
            $table->string('consent_version');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['document_type', 'kind', 'status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
