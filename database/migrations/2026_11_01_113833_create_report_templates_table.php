<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('institution_name');
            $table->string('office_name')->nullable();
            $table->string('monthly_title')->default('OJT Monthly DTR Report');
            $table->string('weekly_title')->default('OJT Weekly Activity Report');
            $table->string('prepared_by_label')->default('Prepared by');
            $table->string('approved_by_label')->default('Approved by');
            $table->string('signatory_name')->nullable();
            $table->string('signatory_title')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};
