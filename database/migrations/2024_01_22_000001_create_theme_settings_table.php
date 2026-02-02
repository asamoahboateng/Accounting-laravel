<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->unique();
            $table->string('sidebar_bg', 7)->default('#1e293b');
            $table->string('sidebar_text', 7)->default('#e2e8f0');
            $table->string('sidebar_text_muted', 7)->default('#94a3b8');
            $table->string('sidebar_hover_bg', 7)->default('#334155');
            $table->string('sidebar_active_bg', 7)->default('#0f172a');
            $table->string('sidebar_border', 7)->default('#334155');
            $table->string('sidebar_brand_bg', 7)->default('#0f172a');
            $table->string('sidebar_accent_color', 7)->default('#10b981');
            $table->string('brand_name', 100)->default('QuickBooks Clone');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
    }
};
