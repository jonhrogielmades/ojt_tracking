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
        Schema::create('document_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('program')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'program', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_requirements');
    }
};
