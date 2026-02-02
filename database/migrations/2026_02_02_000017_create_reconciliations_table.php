<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('bank_account_id');
            $table->uuid('account_id'); // GL Account
            $table->date('statement_date');
            $table->date('statement_start_date');
            $table->date('statement_end_date');
            $table->decimal('statement_balance', 20, 4);
            $table->decimal('opening_balance', 20, 4);
            $table->decimal('cleared_balance', 20, 4)->default(0);
            $table->decimal('difference', 20, 4)->default(0);
            $table->string('status')->default('in_progress'); // in_progress, completed, void
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts');

            $table->index(['company_id', 'bank_account_id', 'statement_date']);
        });

        // Reconciliation line items (cleared transactions)
        Schema::create('reconciliation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('reconciliation_id');
            $table->uuid('journal_entry_line_id');
            $table->decimal('amount', 20, 4);
            $table->boolean('is_cleared')->default(false);
            $table->timestamp('cleared_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('reconciliation_id')->references('id')->on('reconciliations')->cascadeOnDelete();
            $table->foreign('journal_entry_line_id')->references('id')->on('journal_entry_lines');

            $table->unique(['reconciliation_id', 'journal_entry_line_id']);
        });

        // Add reconciliation foreign keys to journal_entry_lines
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->foreign('reconciliation_id')->references('id')->on('reconciliations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropForeign(['reconciliation_id']);
        });
        Schema::dropIfExists('reconciliation_items');
        Schema::dropIfExists('reconciliations');
    }
};
