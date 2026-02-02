<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit logs - THIRD ENTRY of triple-entry system
        // Immutable, cryptographically verifiable audit trail
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('transaction_id')->nullable();
            $table->uuid('journal_entry_id')->nullable();
            $table->string('auditable_type'); // Model class name
            $table->uuid('auditable_id');
            $table->string('event'); // created, updated, deleted, posted, voided, etc.
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('changed_fields')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_email')->nullable();
            $table->string('user_name')->nullable();
            $table->uuid('batch_id')->nullable(); // Groups related changes
            $table->string('previous_hash', 64)->nullable(); // Links to previous log for chain integrity
            $table->string('hash', 64); // SHA-256 hash of this record for integrity verification
            $table->decimal('amount_affected', 20, 4)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();

            $table->index(['company_id', 'created_at']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['company_id', 'event']);
            $table->index(['user_id', 'created_at']);
            $table->index(['batch_id']);
            $table->index(['transaction_id']);
            $table->index(['journal_entry_id']);
        });

        // Audit chain integrity verification checkpoints
        Schema::create('audit_checkpoints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('last_audit_log_id');
            $table->string('checkpoint_hash', 64);
            $table->integer('log_count');
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->string('status')->default('verified'); // verified, broken, pending
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('last_audit_log_id')->references('id')->on('audit_logs');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_checkpoints');
        Schema::dropIfExists('audit_logs');
    }
};
