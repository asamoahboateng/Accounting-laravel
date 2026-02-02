<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Saved report configurations
        Schema::create('report_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('report_type'); // balance_sheet, profit_loss, cash_flow, ar_aging, ap_aging, trial_balance, general_ledger
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_shared')->default(false);
            $table->json('parameters')->nullable();
            $table->json('columns')->nullable();
            $table->json('filters')->nullable();
            $table->json('groupings')->nullable();
            $table->string('date_range_type')->nullable(); // this_month, last_month, this_quarter, custom, etc.
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('comparison_type')->nullable(); // none, previous_period, previous_year, budget
            $table->boolean('show_zero_balances')->default(false);
            $table->string('accounting_method')->default('accrual'); // accrual, cash
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->index(['company_id', 'report_type']);
        });

        // Scheduled report deliveries
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('report_configuration_id');
            $table->string('name');
            $table->string('frequency'); // daily, weekly, monthly
            $table->integer('day_of_week')->nullable();
            $table->integer('day_of_month')->nullable();
            $table->string('time', 5)->default('08:00');
            $table->string('timezone')->default('UTC');
            $table->json('recipients'); // Array of email addresses
            $table->string('format')->default('pdf'); // pdf, excel, csv
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('report_configuration_id')->references('id')->on('report_configurations')->cascadeOnDelete();
        });

        // Report snapshots for historical comparison
        Schema::create('report_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('report_type');
            $table->string('snapshot_name');
            $table->date('as_of_date');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->json('data');
            $table->json('parameters')->nullable();
            $table->boolean('is_official')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->index(['company_id', 'report_type', 'as_of_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_snapshots');
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('report_configurations');
    }
};
