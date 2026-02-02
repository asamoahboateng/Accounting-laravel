<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main transactions table - FIRST ENTRY of triple-entry system
        // This represents the business event/document
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('transaction_type'); // invoice, bill, payment, journal, transfer, etc.
            $table->string('transaction_number');
            $table->uuid('reference_id')->nullable(); // Links to source document (invoice_id, bill_id, etc.)
            $table->string('reference_type')->nullable(); // invoice, bill, payment, etc.
            $table->date('transaction_date');
            $table->date('due_date')->nullable();
            $table->date('posting_date');
            $table->uuid('fiscal_period_id')->nullable();
            $table->uuid('contact_id')->nullable();
            $table->string('contact_type')->nullable(); // customer, vendor
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_total', 20, 4)->default(0);
            $table->decimal('discount_total', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->decimal('base_currency_total', 20, 4)->default(0);
            $table->decimal('amount_paid', 20, 4)->default(0);
            $table->decimal('balance_due', 20, 4)->default(0);
            $table->string('status')->default('draft'); // draft, pending, posted, void, paid
            $table->string('approval_status')->default('approved'); // pending, approved, rejected
            $table->text('memo')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('external_reference')->nullable();
            $table->json('metadata')->nullable();
            $table->json('line_items_snapshot')->nullable(); // Immutable snapshot for audit
            $table->boolean('is_recurring')->default(false);
            $table->uuid('recurring_schedule_id')->nullable();
            $table->boolean('is_manual')->default(false);
            $table->boolean('is_adjusting')->default(false);
            $table->boolean('is_closing')->default(false);
            $table->boolean('is_reversed')->default(false);
            $table->uuid('reversal_of_id')->nullable();
            $table->uuid('reversed_by_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('fiscal_period_id')->references('id')->on('fiscal_periods')->nullOnDelete();
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->foreign('reversal_of_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('reversed_by_id')->references('id')->on('transactions')->nullOnDelete();

            $table->unique(['company_id', 'transaction_type', 'transaction_number']);
            $table->index(['company_id', 'transaction_date']);
            $table->index(['company_id', 'transaction_type', 'status']);
            $table->index(['company_id', 'contact_id', 'contact_type']);
            $table->index(['company_id', 'posting_date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
