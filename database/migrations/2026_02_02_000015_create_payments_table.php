<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Payment methods
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->string('type'); // cash, check, credit_card, bank_transfer, other
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });

        // Customer payments (received)
        Schema::create('payments_received', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('transaction_id')->nullable();
            $table->uuid('customer_id');
            $table->string('payment_number');
            $table->date('payment_date');
            $table->uuid('payment_method_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 20, 10)->default(1);

            $table->decimal('amount', 20, 4);
            $table->decimal('amount_applied', 20, 4)->default(0);
            $table->decimal('amount_unapplied', 20, 4)->default(0);
            $table->decimal('base_currency_amount', 20, 4);

            $table->uuid('deposit_account_id');
            $table->uuid('receivable_account_id')->nullable();
            $table->string('status')->default('pending'); // pending, deposited, void

            $table->text('memo')->nullable();
            $table->json('metadata')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('contacts');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->foreign('deposit_account_id')->references('id')->on('accounts');
            $table->foreign('receivable_account_id')->references('id')->on('accounts')->nullOnDelete();

            $table->unique(['company_id', 'payment_number']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'payment_date']);
        });

        // Payment applications (which invoices the payment covers)
        Schema::create('payment_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('payment_received_id');
            $table->uuid('invoice_id');
            $table->decimal('amount_applied', 20, 4);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('payment_received_id')->references('id')->on('payments_received')->cascadeOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices');

            $table->unique(['payment_received_id', 'invoice_id']);
        });

        // Vendor payments (paid)
        Schema::create('payments_made', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('transaction_id')->nullable();
            $table->uuid('vendor_id');
            $table->string('payment_number');
            $table->date('payment_date');
            $table->uuid('payment_method_id')->nullable();
            $table->string('check_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 20, 10)->default(1);

            $table->decimal('amount', 20, 4);
            $table->decimal('amount_applied', 20, 4)->default(0);
            $table->decimal('amount_unapplied', 20, 4)->default(0);
            $table->decimal('base_currency_amount', 20, 4);

            $table->uuid('bank_account_id');
            $table->uuid('payable_account_id')->nullable();
            $table->string('status')->default('pending'); // pending, cleared, void

            $table->text('memo')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_print_later')->default(false);
            $table->json('metadata')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('vendor_id')->references('id')->on('contacts');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->foreign('bank_account_id')->references('id')->on('accounts');
            $table->foreign('payable_account_id')->references('id')->on('accounts')->nullOnDelete();

            $table->unique(['company_id', 'payment_number']);
            $table->index(['company_id', 'vendor_id']);
            $table->index(['company_id', 'payment_date']);
        });

        // Bill payment applications
        Schema::create('bill_payment_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('payment_made_id');
            $table->string('payable_type'); // bill, vendor_credit
            $table->uuid('payable_id');
            $table->decimal('amount_applied', 20, 4);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('payment_made_id')->references('id')->on('payments_made')->cascadeOnDelete();

            $table->unique(['payment_made_id', 'payable_type', 'payable_id'], 'bill_payment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_payment_applications');
        Schema::dropIfExists('payments_made');
        Schema::dropIfExists('payment_applications');
        Schema::dropIfExists('payments_received');
        Schema::dropIfExists('payment_methods');
    }
};
