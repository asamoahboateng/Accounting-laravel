<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('vendor_id');
            $table->string('po_number');
            $table->string('status')->default('draft'); // draft, sent, partial, received, closed, cancelled
            $table->date('po_date');
            $table->date('expected_date')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 20, 10)->default(1);

            $table->text('shipping_address')->nullable();
            $table->string('ship_via')->nullable();

            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('shipping_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);

            $table->uuid('inventory_location_id')->nullable();

            $table->text('memo')->nullable();
            $table->text('vendor_message')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('vendor_id')->references('id')->on('contacts');
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->foreign('inventory_location_id')->references('id')->on('inventory_locations')->nullOnDelete();

            $table->unique(['company_id', 'po_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'vendor_id']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('purchase_order_id');
            $table->integer('line_number');
            $table->uuid('product_id')->nullable();
            $table->uuid('account_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 20, 4)->default(1);
            $table->decimal('quantity_received', 20, 4)->default(0);
            $table->decimal('quantity_billed', 20, 4)->default(0);
            $table->uuid('unit_of_measure_id')->nullable();
            $table->decimal('unit_cost', 20, 4)->default(0);
            $table->decimal('amount', 20, 4)->default(0);
            $table->uuid('tax_rate_id')->nullable();
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->uuid('customer_id')->nullable();
            $table->boolean('is_billable')->default(false);
            $table->date('expected_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('unit_of_measure_id')->references('id')->on('units_of_measure')->nullOnDelete();
            $table->foreign('tax_rate_id')->references('id')->on('tax_rates')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('contacts')->nullOnDelete();
        });

        // Add purchase_order_id foreign key to bills
        Schema::table('bills', function (Blueprint $table) {
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
        });
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
