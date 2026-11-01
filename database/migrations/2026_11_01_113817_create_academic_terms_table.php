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
        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedInteger('required_minutes')->default(0);
            $table->json('programs')->nullable();
            $table->json('sections')->nullable();
            $table->json('holidays')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'starts_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_terms');
    }
};
