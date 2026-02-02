<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bank account connections (Plaid-style integration)
        Schema::create('bank_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('provider')->default('plaid'); // plaid, yodlee, manual
            $table->string('institution_id')->nullable();
            $table->string('institution_name');
            $table->string('institution_logo')->nullable();
            $table->string('access_token')->nullable(); // Encrypted
            $table->string('item_id')->nullable();
            $table->string('status')->default('active'); // active, error, disconnected
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('consent_expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->index(['company_id', 'status']);
        });

        // Bank accounts linked to GL accounts
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('account_id'); // Link to chart of accounts
            $table->uuid('bank_connection_id')->nullable();
            $table->string('name');
            $table->string('account_type'); // checking, savings, credit_card, money_market
            $table->string('account_number_last4', 4)->nullable();
            $table->string('routing_number')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('current_balance', 20, 4)->default(0);
            $table->decimal('available_balance', 20, 4)->default(0);
            $table->decimal('statement_balance', 20, 4)->default(0);
            $table->date('statement_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('bank_connection_id')->references('id')->on('bank_connections')->nullOnDelete();
            $table->foreign('currency_code')->references('code')->on('currencies');

            $table->unique(['company_id', 'account_id']);
        });

        // Bank transactions (imported from feeds or manual entry)
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('bank_account_id');
            $table->string('external_id')->nullable(); // From bank feed
            $table->date('transaction_date');
            $table->date('posted_date')->nullable();
            $table->string('transaction_type'); // debit, credit
            $table->decimal('amount', 20, 4);
            $table->string('description')->nullable();
            $table->string('payee')->nullable();
            $table->string('check_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('category')->nullable(); // Bank's category
            $table->string('status')->default('pending'); // pending, matched, categorized, excluded, reconciled
            $table->uuid('matched_transaction_id')->nullable();
            $table->string('matched_transaction_type')->nullable();
            $table->uuid('categorized_account_id')->nullable();
            $table->uuid('categorized_contact_id')->nullable();
            $table->uuid('rule_id')->nullable();
            $table->boolean('is_manual')->default(false);
            $table->decimal('running_balance', 20, 4)->nullable();
            $table->json('raw_data')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->cascadeOnDelete();
            $table->foreign('categorized_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('categorized_contact_id')->references('id')->on('contacts')->nullOnDelete();

            $table->unique(['bank_account_id', 'external_id']);
            $table->index(['company_id', 'transaction_date']);
            $table->index(['bank_account_id', 'status']);
        });

        // Bank feed categorization rules
        Schema::create('bank_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);

            // Conditions (AND logic)
            $table->string('description_contains')->nullable();
            $table->string('description_equals')->nullable();
            $table->decimal('amount_min', 20, 4)->nullable();
            $table->decimal('amount_max', 20, 4)->nullable();
            $table->decimal('amount_equals', 20, 4)->nullable();
            $table->string('transaction_type')->nullable(); // debit, credit

            // Actions
            $table->string('action'); // categorize, transfer, split, exclude
            $table->uuid('account_id')->nullable();
            $table->uuid('contact_id')->nullable();
            $table->uuid('tax_rate_id')->nullable();
            $table->string('memo_template')->nullable();
            $table->boolean('auto_confirm')->default(false);
            $table->json('split_lines')->nullable();

            $table->integer('times_applied')->default(0);
            $table->timestamp('last_applied_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('contact_id')->references('id')->on('contacts')->nullOnDelete();
            $table->foreign('tax_rate_id')->references('id')->on('tax_rates')->nullOnDelete();

            $table->index(['company_id', 'is_active', 'priority']);
        });

        // Add rule_id foreign key to bank_transactions
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->foreign('rule_id')->references('id')->on('bank_rules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropForeign(['rule_id']);
        });
        Schema::dropIfExists('bank_rules');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('bank_connections');
    }
};
