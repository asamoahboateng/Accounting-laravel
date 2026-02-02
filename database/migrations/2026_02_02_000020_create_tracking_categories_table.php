<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Classes/Departments for tracking
        Schema::create('tracking_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('type'); // class, department, location, project
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('tracking_categories')->nullOnDelete();
            $table->index(['company_id', 'type', 'is_active']);
        });

        // Projects for job costing
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('customer_id')->nullable();
            $table->string('name');
            $table->string('code', 30)->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, completed, on_hold, cancelled
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('budget_amount', 20, 4)->nullable();
            $table->decimal('estimated_hours', 15, 2)->nullable();
            $table->decimal('actual_hours', 15, 2)->default(0);
            $table->decimal('actual_cost', 20, 4)->default(0);
            $table->decimal('invoiced_amount', 20, 4)->default(0);
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('contacts')->nullOnDelete();
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status']);
        });

        // Add project_id to invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });

        // Add foreign keys to journal_entry_lines
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('tracking_categories')->nullOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('tracking_categories')->nullOnDelete();
            $table->foreign('class_id')->references('id')->on('tracking_categories')->nullOnDelete();
        });

        // Add foreign keys to invoice_lines
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->foreign('class_id')->references('id')->on('tracking_categories')->nullOnDelete();
        });

        // Add foreign keys to bill_lines
        Schema::table('bill_lines', function (Blueprint $table) {
            $table->foreign('class_id')->references('id')->on('tracking_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bill_lines', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
        });
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
        });
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['project_id']);
            $table->dropForeign(['location_id']);
            $table->dropForeign(['class_id']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });
        Schema::dropIfExists('projects');
        Schema::dropIfExists('tracking_categories');
    }
};
