<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Product categories
        Schema::create('product_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('product_categories')->nullOnDelete();
        });

        // Units of measure
        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->string('abbreviation', 10);
            $table->boolean('is_base_unit')->default(false);
            $table->uuid('base_unit_id')->nullable();
            $table->decimal('conversion_factor', 20, 10)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('base_unit_id')->references('id')->on('units_of_measure')->nullOnDelete();
        });

        // Products and services
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('category_id')->nullable();
            $table->string('type'); // inventory, non_inventory, service, bundle
            $table->string('sku')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('sales_description')->nullable();
            $table->text('purchase_description')->nullable();

            // Pricing
            $table->decimal('sales_price', 20, 4)->default(0);
            $table->decimal('purchase_cost', 20, 4)->default(0);
            $table->string('currency_code', 3)->default('USD');

            // Inventory settings
            $table->boolean('track_inventory')->default(false);
            $table->uuid('unit_of_measure_id')->nullable();
            $table->decimal('quantity_on_hand', 20, 4)->default(0);
            $table->decimal('quantity_on_order', 20, 4)->default(0);
            $table->decimal('quantity_committed', 20, 4)->default(0);
            $table->decimal('reorder_point', 20, 4)->nullable();
            $table->decimal('reorder_quantity', 20, 4)->nullable();

            // Costing - Moving Average Cost (MAC)
            $table->string('costing_method')->default('moving_average'); // fifo, lifo, moving_average, specific
            $table->decimal('average_cost', 20, 4)->default(0);
            $table->decimal('last_cost', 20, 4)->default(0);
            $table->decimal('standard_cost', 20, 4)->nullable();

            // Accounts
            $table->uuid('income_account_id')->nullable();
            $table->uuid('expense_account_id')->nullable();
            $table->uuid('asset_account_id')->nullable();
            $table->uuid('cogs_account_id')->nullable();

            // Tax
            $table->uuid('sales_tax_rate_id')->nullable();
            $table->uuid('purchase_tax_rate_id')->nullable();
            $table->boolean('is_taxable')->default(true);

            // Physical attributes
            $table->decimal('weight', 15, 4)->nullable();
            $table->string('weight_unit', 10)->nullable();
            $table->decimal('length', 15, 4)->nullable();
            $table->decimal('width', 15, 4)->nullable();
            $table->decimal('height', 15, 4)->nullable();
            $table->string('dimension_unit', 10)->nullable();

            $table->string('barcode')->nullable();
            $table->string('image_path')->nullable();
            $table->json('images')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sold')->default(true);
            $table->boolean('is_purchased')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('product_categories')->nullOnDelete();
            $table->foreign('unit_of_measure_id')->references('id')->on('units_of_measure')->nullOnDelete();
            $table->foreign('currency_code')->references('code')->on('currencies');
            $table->foreign('income_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('expense_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('asset_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('cogs_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('sales_tax_rate_id')->references('id')->on('tax_rates')->nullOnDelete();
            $table->foreign('purchase_tax_rate_id')->references('id')->on('tax_rates')->nullOnDelete();

            $table->unique(['company_id', 'sku']);
            $table->index(['company_id', 'type', 'is_active']);
            $table->index(['company_id', 'name']);
        });

        // Product bundles/assemblies
        Schema::create('product_bundles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('bundle_product_id');
            $table->uuid('component_product_id');
            $table->decimal('quantity', 20, 4)->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('bundle_product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('component_product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        // Inventory locations
        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('inventory_locations')->nullOnDelete();
            $table->unique(['company_id', 'code']);
        });

        // Product inventory by location
        Schema::create('product_inventory', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('product_id');
            $table->uuid('location_id');
            $table->decimal('quantity_on_hand', 20, 4)->default(0);
            $table->decimal('quantity_committed', 20, 4)->default(0);
            $table->decimal('quantity_on_order', 20, 4)->default(0);
            $table->decimal('average_cost', 20, 4)->default(0);
            $table->string('bin_location')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('inventory_locations')->cascadeOnDelete();
            $table->unique(['product_id', 'location_id']);
        });

        // Inventory transactions for MAC calculation
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('product_id');
            $table->uuid('location_id');
            $table->uuid('transaction_id')->nullable();
            $table->string('movement_type'); // purchase, sale, adjustment, transfer, assembly
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->date('movement_date');
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_cost', 20, 4);
            $table->decimal('total_cost', 20, 4);
            $table->decimal('quantity_before', 20, 4);
            $table->decimal('quantity_after', 20, 4);
            $table->decimal('average_cost_before', 20, 4);
            $table->decimal('average_cost_after', 20, 4);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('inventory_locations')->cascadeOnDelete();
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();

            $table->index(['company_id', 'product_id', 'movement_date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('product_inventory');
        Schema::dropIfExists('inventory_locations');
        Schema::dropIfExists('product_bundles');
        Schema::dropIfExists('products');
        Schema::dropIfExists('units_of_measure');
        Schema::dropIfExists('product_categories');
    }
};
