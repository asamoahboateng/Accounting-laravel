<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vehicles for mileage tracking
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('year', 4)->nullable();
            $table->string('license_plate')->nullable();
            $table->string('vin')->nullable();
            $table->decimal('odometer_reading', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });

        // Mileage rates by year
        Schema::create('mileage_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->integer('year');
            $table->string('rate_type')->default('standard'); // standard, charity, medical, moving
            $table->decimal('rate_per_mile', 10, 4);
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->index(['company_id', 'year', 'rate_type']);
        });

        // Mileage entries
        Schema::create('mileage_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('transaction_id')->nullable();
            $table->uuid('vehicle_id')->nullable();
            $table->uuid('mileage_rate_id')->nullable();
            $table->date('trip_date');
            $table->string('purpose');
            $table->string('start_location')->nullable();
            $table->string('end_location')->nullable();
            $table->decimal('distance', 15, 2);
            $table->string('distance_unit', 10)->default('miles');
            $table->decimal('rate_per_unit', 10, 4)->nullable();
            $table->decimal('total_amount', 20, 4);
            $table->uuid('expense_account_id')->nullable();
            $table->uuid('customer_id')->nullable();
            $table->boolean('is_billable')->default(false);
            $table->boolean('is_round_trip')->default(false);
            $table->string('status')->default('pending'); // pending, approved, reimbursed
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
            $table->foreign('mileage_rate_id')->references('id')->on('mileage_rates')->nullOnDelete();
            $table->foreign('expense_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('contacts')->nullOnDelete();

            $table->index(['company_id', 'trip_date']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mileage_entries');
        Schema::dropIfExists('mileage_rates');
        Schema::dropIfExists('vehicles');
    }
};
