<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->string('template_type'); // invoice, bill, journal_entry
            $table->uuid('template_id');
            $table->string('frequency'); // daily, weekly, monthly, quarterly, annually
            $table->integer('interval')->default(1);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date');
            $table->date('last_run_date')->nullable();
            $table->integer('day_of_month')->nullable();
            $table->integer('day_of_week')->nullable();
            $table->integer('occurrences_limit')->nullable();
            $table->integer('occurrences_count')->default(0);
            $table->integer('days_before_due')->nullable();
            $table->boolean('auto_send')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->index(['company_id', 'is_active', 'next_run_date']);
        });

        // Add recurring_schedule_id foreign keys
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('recurring_schedule_id')->references('id')->on('recurring_schedules')->nullOnDelete();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->foreign('recurring_schedule_id')->references('id')->on('recurring_schedules')->nullOnDelete();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('recurring_schedule_id')->references('id')->on('recurring_schedules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['recurring_schedule_id']);
        });
        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['recurring_schedule_id']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['recurring_schedule_id']);
        });
        Schema::dropIfExists('recurring_schedules');
    }
};
