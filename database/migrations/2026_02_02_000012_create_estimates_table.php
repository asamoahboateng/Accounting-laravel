<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('customer_id');
            $table->string('estimate_number');
            $table->string('status')->default('draft'); // draft, sent, viewed, accepted, rejected, expired, converted
            $table->date('estimate_date');
            $table->date('expiration_date')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 20, 10)->default(1);

            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();

            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->string('discount_type')->nullable();
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);

            $table->text('message')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('acceptance_signature')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('contacts');
            $table->foreign('currency_code')->references('code')->on('currencies');

            $table->unique(['company_id', 'estimate_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'customer_id']);
        });

        Schema::create('estimate_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('estimate_id');
            $table->integer('line_number');
            $table->string('line_type')->default('item');
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
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('estimate_id')->references('id')->on('estimates')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('unit_of_measure_id')->references('id')->on('units_of_measure')->nullOnDelete();
            $table->foreign('tax_rate_id')->references('id')->on('tax_rates')->nullOnDelete();
        });

        // Add estimate_id foreign key to invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('estimate_id')->references('id')->on('estimates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['estimate_id']);
        });
        Schema::dropIfExists('estimate_lines');
        Schema::dropIfExists('estimates');
    }
};
