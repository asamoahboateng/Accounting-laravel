<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('transaction_id')->nullable();
            $table->uuid('customer_id');
            $table->string('invoice_number');
            $table->uuid('estimate_id')->nullable();
            $table->string('status')->default('draft'); // draft, sent, viewed, partial, paid, overdue, void
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('payment_terms')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 20, 10)->default(1);

            // Addresses
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('ship_via')->nullable();
            $table->date('ship_date')->nullable();
            $table->string('tracking_number')->nullable();

            // Amounts
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('discount_type_value', 20, 4)->default(0);
            $table->string('discount_type')->nullable(); // percentage, fixed
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('shipping_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->decimal('amount_paid', 20, 4)->default(0);
            $table->decimal('balance_due', 20, 4)->default(0);
            $table->decimal('base_currency_total', 20, 4)->default(0);

            // Accounts
            $table->uuid('receivable_account_id')->nullable();
            $table->uuid('deposit_account_id')->nullable();
            $table->decimal('deposit_amount', 20, 4)->default(0);

            // Progress billing
            $table->boolean('is_progress_billing')->default(false);
            $table->decimal('progress_percentage', 10, 4)->nullable();
            $table->uuid('project_id')->nullable();

            // Recurring
            $table->boolean('is_recurring')->default(false);
            $table->uuid('recurring_schedule_id')->nullable();

            // Content
            $table->text('message')->nullable();
            $table->text('statement_message')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('custom_field_1')->nullable();
            $table->string('custom_field_2')->nullable();
            $table->string('custom_field_3')->nullable();
            $table->json('metadata')->nullable();

            // Attachments
            $table->json('attachments')->nullable();

            // Tracking
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('last_reminder_at')->nullable();
            $table->integer('reminder_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('contacts');
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->foreign('receivable_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('deposit_account_id')->references('id')->on('accounts')->nullOnDelete();

            $table->unique(['company_id', 'invoice_number']);
            $table->index(['company_id', 'status', 'due_date']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'invoice_date']);
        });

        // Invoice line items
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('invoice_id');
            $table->integer('line_number');
            $table->string('line_type')->default('item'); // item, subtotal, discount, description
            $table->uuid('product_id')->nullable();
            $table->uuid('account_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 20, 4)->default(1);
            $table->uuid('unit_of_measure_id')->nullable();
            $table->decimal('unit_price', 20, 4)->default(0);
            $table->decimal('discount_percent', 10, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('amount', 20, 4)->default(0);
            $table->uuid('tax_rate_id')->nullable();
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->uuid('class_id')->nullable();
            $table->uuid('location_id')->nullable();
            $table->date('service_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('unit_of_measure_id')->references('id')->on('units_of_measure')->nullOnDelete();
            $table->foreign('tax_rate_id')->references('id')->on('tax_rates')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('inventory_locations')->nullOnDelete();

            $table->index(['invoice_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
    }
};
