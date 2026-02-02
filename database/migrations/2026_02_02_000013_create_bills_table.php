<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vendor Bills
        Schema::create('bills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('transaction_id')->nullable();
            $table->uuid('vendor_id');
            $table->string('bill_number');
            $table->string('vendor_invoice_number')->nullable();
            $table->uuid('purchase_order_id')->nullable();
            $table->string('status')->default('draft'); // draft, pending, partial, paid, overdue, void
            $table->date('bill_date');
            $table->date('due_date');
            $table->string('payment_terms')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 20, 10)->default(1);

            $table->text('mailing_address')->nullable();

            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->decimal('amount_paid', 20, 4)->default(0);
            $table->decimal('balance_due', 20, 4)->default(0);
            $table->decimal('base_currency_total', 20, 4)->default(0);

            $table->uuid('payable_account_id')->nullable();

            $table->boolean('is_recurring')->default(false);
            $table->uuid('recurring_schedule_id')->nullable();

            $table->text('memo')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('vendor_id')->references('id')->on('contacts');
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->foreign('payable_account_id')->references('id')->on('accounts')->nullOnDelete();

            $table->unique(['company_id', 'bill_number']);
            $table->index(['company_id', 'status', 'due_date']);
            $table->index(['company_id', 'vendor_id']);
        });

        Schema::create('bill_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('bill_id');
            $table->integer('line_number');
            $table->string('line_type')->default('item'); // item, expense
            $table->uuid('product_id')->nullable();
            $table->uuid('account_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 20, 4)->default(1);
            $table->uuid('unit_of_measure_id')->nullable();
            $table->decimal('unit_cost', 20, 4)->default(0);
            $table->decimal('amount', 20, 4)->default(0);
            $table->uuid('tax_rate_id')->nullable();
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->boolean('is_billable')->default(false);
            $table->uuid('customer_id')->nullable();
            $table->uuid('class_id')->nullable();
            $table->uuid('location_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('bill_id')->references('id')->on('bills')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('unit_of_measure_id')->references('id')->on('units_of_measure')->nullOnDelete();
            $table->foreign('tax_rate_id')->references('id')->on('tax_rates')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('contacts')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('inventory_locations')->nullOnDelete();
        });

        // Vendor Credits
        Schema::create('vendor_credits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('transaction_id')->nullable();
            $table->uuid('vendor_id');
            $table->string('credit_number');
            $table->string('vendor_credit_number')->nullable();
            $table->uuid('bill_id')->nullable();
            $table->string('status')->default('draft'); // draft, open, partial, applied, void
            $table->date('credit_date');
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 20, 10)->default(1);

            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->decimal('amount_applied', 20, 4)->default(0);
            $table->decimal('balance_remaining', 20, 4)->default(0);

            $table->uuid('payable_account_id')->nullable();
            $table->text('memo')->nullable();
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('vendor_id')->references('id')->on('contacts');
            $table->foreign('bill_id')->references('id')->on('bills')->nullOnDelete();
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->foreign('payable_account_id')->references('id')->on('accounts')->nullOnDelete();

            $table->unique(['company_id', 'credit_number']);
        });

        Schema::create('vendor_credit_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('vendor_credit_id');
            $table->integer('line_number');
            $table->uuid('product_id')->nullable();
            $table->uuid('account_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 20, 4)->default(1);
            $table->decimal('unit_cost', 20, 4)->default(0);
            $table->decimal('amount', 20, 4)->default(0);
            $table->uuid('tax_rate_id')->nullable();
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('vendor_credit_id')->references('id')->on('vendor_credits')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('tax_rate_id')->references('id')->on('tax_rates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_credit_lines');
        Schema::dropIfExists('vendor_credits');
        Schema::dropIfExists('bill_lines');
        Schema::dropIfExists('bills');
    }
};
