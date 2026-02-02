<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Account types (Assets, Liabilities, Equity, Revenue, Expenses)
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->string('classification'); // asset, liability, equity, revenue, expense
            $table->string('normal_balance'); // debit, credit
            $table->integer('display_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // Account subtypes for more granular categorization
        Schema::create('account_subtypes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->text('description')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // Chart of Accounts - hierarchical structure
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('parent_id')->nullable();
            $table->foreignId('account_type_id')->constrained();
            $table->foreignId('account_subtype_id')->nullable()->constrained();
            $table->string('code', 20);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_bank_account')->default(false);
            $table->boolean('is_tax_account')->default(false);
            $table->boolean('is_header_account')->default(false);
            $table->boolean('is_sub_account')->default(false);
            $table->integer('depth')->default(0);
            $table->string('full_path')->nullable();
            $table->decimal('opening_balance', 20, 4)->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->decimal('current_balance', 20, 4)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('currency_code')->references('code')->on('currencies');

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'account_type_id', 'is_active']);
            $table->index(['company_id', 'parent_id']);
        });

        // Account balances by period for faster reporting
        Schema::create('account_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('account_id');
            $table->integer('fiscal_year');
            $table->integer('period'); // 1-12 for months, or custom periods
            $table->decimal('opening_debit', 20, 4)->default(0);
            $table->decimal('opening_credit', 20, 4)->default(0);
            $table->decimal('period_debit', 20, 4)->default(0);
            $table->decimal('period_credit', 20, 4)->default(0);
            $table->decimal('closing_debit', 20, 4)->default(0);
            $table->decimal('closing_credit', 20, 4)->default(0);
            $table->decimal('ytd_debit', 20, 4)->default(0);
            $table->decimal('ytd_credit', 20, 4)->default(0);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->unique(['account_id', 'fiscal_year', 'period']);
            $table->index(['company_id', 'fiscal_year', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_balances');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('account_subtypes');
        Schema::dropIfExists('account_types');
    }
};
