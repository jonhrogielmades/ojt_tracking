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
        Schema::create('correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dtr_entry_id')->constrained()->cascadeOnDelete();
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->boolean('is_open')->nullable()->default(true);
            $table->text('reviewer_response')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['dtr_entry_id', 'status']);
            $table->unique(['dtr_entry_id', 'is_open']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correction_requests');
    }
};
