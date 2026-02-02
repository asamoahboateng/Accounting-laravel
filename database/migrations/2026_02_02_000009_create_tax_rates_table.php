<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tax agencies (IRS, State Revenue, VAT Authority, etc.)
        Schema::create('tax_agencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->uuid('payable_account_id')->nullable();
            $table->string('filing_frequency')->nullable(); // monthly, quarterly, annually
            $table->string('country_code', 2)->nullable();
            $table->string('state_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('payable_account_id')->references('id')->on('accounts')->nullOnDelete();
        });

        // Tax rates (GST, VAT, Sales Tax, etc.)
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('tax_agency_id')->nullable();
            $table->string('name');
            $table->string('code', 20);
            $table->text('description')->nullable();
            $table->string('tax_type'); // sales, purchase, both
            $table->decimal('rate', 10, 4);
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_inclusive')->default(false);
            $table->uuid('account_id')->nullable(); // Tax liability/expense account
            $table->string('country_code', 2)->nullable();
            $table->string('state_code')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('tax_agency_id')->references('id')->on('tax_agencies')->nullOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'tax_type', 'is_active']);
        });

        // Compound/grouped tax rates
        Schema::create('tax_rate_components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tax_rate_id');
            $table->uuid('component_tax_rate_id');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->foreign('tax_rate_id')->references('id')->on('tax_rates')->cascadeOnDelete();
            $table->foreign('component_tax_rate_id')->references('id')->on('tax_rates')->cascadeOnDelete();
        });

        // Add tax_rate_id foreign key to contacts
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreign('tax_rate_id')->references('id')->on('tax_rates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['tax_rate_id']);
        });
        Schema::dropIfExists('tax_rate_components');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('tax_agencies');
    }
};
