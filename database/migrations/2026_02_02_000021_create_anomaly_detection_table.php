<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AI Anomaly detection results for Books Close
        Schema::create('anomaly_detections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('fiscal_period_id')->nullable();
            $table->string('detection_type'); // unusual_amount, duplicate, missing_entry, timing, pattern
            $table->string('severity'); // info, warning, critical
            $table->string('status')->default('open'); // open, reviewed, resolved, dismissed
            $table->string('entity_type'); // transaction, journal_entry, invoice, bill, etc.
            $table->uuid('entity_id');
            $table->string('anomaly_code', 50);
            $table->string('title');
            $table->text('description');
            $table->decimal('confidence_score', 5, 4)->nullable(); // 0.0000 to 1.0000
            $table->json('detection_data')->nullable();
            $table->json('suggested_actions')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('fiscal_period_id')->references('id')->on('fiscal_periods')->nullOnDelete();

            $table->index(['company_id', 'status', 'severity']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['company_id', 'fiscal_period_id']);
        });

        // Anomaly detection rules (customizable per company)
        Schema::create('anomaly_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('rule_type'); // threshold, pattern, statistical
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('entity_type');
            $table->json('conditions');
            $table->string('severity')->default('warning');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->index(['company_id', 'is_active', 'entity_type']);
        });

        // Books close process tracking
        Schema::create('books_close_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('fiscal_period_id');
            $table->string('status')->default('running'); // running, completed, failed
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('transactions_processed')->default(0);
            $table->integer('anomalies_found')->default(0);
            $table->integer('warnings_count')->default(0);
            $table->integer('errors_count')->default(0);
            $table->json('summary')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('fiscal_period_id')->references('id')->on('fiscal_periods');

            $table->index(['company_id', 'fiscal_period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books_close_runs');
        Schema::dropIfExists('anomaly_rules');
        Schema::dropIfExists('anomaly_detections');
    }
};
