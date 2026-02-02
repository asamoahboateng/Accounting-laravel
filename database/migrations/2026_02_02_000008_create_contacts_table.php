<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('type'); // customer, vendor, both
            $table->string('display_name');
            $table->string('company_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('fax')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('account_number')->nullable();

            // Billing address
            $table->text('billing_address_line_1')->nullable();
            $table->text('billing_address_line_2')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_postal_code')->nullable();
            $table->string('billing_country', 2)->nullable();

            // Shipping address
            $table->text('shipping_address_line_1')->nullable();
            $table->text('shipping_address_line_2')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_postal_code')->nullable();
            $table->string('shipping_country', 2)->nullable();

            $table->string('currency_code', 3)->default('USD');
            $table->string('payment_terms')->nullable();
            $table->integer('payment_terms_days')->default(30);
            $table->decimal('credit_limit', 20, 4)->nullable();
            $table->decimal('opening_balance', 20, 4)->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->decimal('current_balance', 20, 4)->default(0);

            // Default accounts
            $table->uuid('default_receivable_account_id')->nullable();
            $table->uuid('default_payable_account_id')->nullable();
            $table->uuid('default_expense_account_id')->nullable();
            $table->uuid('default_income_account_id')->nullable();

            $table->uuid('tax_rate_id')->nullable();
            $table->boolean('is_tax_exempt')->default(false);
            $table->string('tax_exemption_reason')->nullable();

            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_1099_eligible')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->foreign('default_receivable_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('default_payable_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('default_expense_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('default_income_account_id')->references('id')->on('accounts')->nullOnDelete();

            $table->index(['company_id', 'type', 'is_active']);
            $table->index(['company_id', 'display_name']);
            $table->index(['company_id', 'email']);
        });

        // Contact persons (multiple contacts per company)
        Schema::create('contact_persons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('contact_id');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('contact_id')->references('id')->on('contacts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_persons');
        Schema::dropIfExists('contacts');
    }
};
