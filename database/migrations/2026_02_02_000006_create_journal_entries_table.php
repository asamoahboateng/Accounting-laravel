<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Journal entries - SECOND ENTRY of triple-entry system
        // Double-entry bookkeeping (debits and credits)
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('transaction_id');
            $table->string('entry_number');
            $table->date('entry_date');
            $table->date('posting_date');
            $table->uuid('fiscal_period_id')->nullable();
            $table->string('entry_type')->default('standard'); // standard, adjusting, closing, reversing
            $table->string('source_type'); // invoice, bill, payment, manual, system
            $table->uuid('source_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('total_debit', 20, 4)->default(0);
            $table->decimal('total_credit', 20, 4)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->string('status')->default('draft'); // draft, posted, void
            $table->boolean('is_balanced')->default(false);
            $table->boolean('is_auto_generated')->default(false);
            $table->boolean('is_reversing')->default(false);
            $table->date('auto_reverse_date')->nullable();
            $table->uuid('reversed_by_entry_id')->nullable();
            $table->uuid('reversal_of_entry_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnDelete();
            $table->foreign('fiscal_period_id')->references('id')->on('fiscal_periods')->nullOnDelete();
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->foreign('reversed_by_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('reversal_of_entry_id')->references('id')->on('journal_entries')->nullOnDelete();

            $table->unique(['company_id', 'entry_number']);
            $table->index(['company_id', 'entry_date']);
            $table->index(['company_id', 'posting_date']);
            $table->index(['transaction_id']);
            $table->index(['source_type', 'source_id']);
        });

        // Journal entry line items (individual debit/credit entries)
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('journal_entry_id');
            $table->uuid('account_id');
            $table->integer('line_number');
            $table->string('type'); // debit, credit
            $table->decimal('amount', 20, 4);
            $table->decimal('base_currency_amount', 20, 4);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->text('description')->nullable();
            $table->uuid('contact_id')->nullable();
            $table->string('contact_type')->nullable();
            $table->uuid('department_id')->nullable();
            $table->uuid('project_id')->nullable();
            $table->uuid('location_id')->nullable();
            $table->uuid('class_id')->nullable();
            $table->uuid('tax_rate_id')->nullable();
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->uuid('reconciliation_id')->nullable();
            $table->boolean('is_reconciled')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('currency_code')->references('code')->on('currencies');

            $table->index(['journal_entry_id', 'line_number']);
            $table->index(['account_id', 'type']);
            $table->index(['company_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
    }
};
