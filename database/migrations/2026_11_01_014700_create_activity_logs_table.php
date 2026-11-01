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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('activity_date');
            $table->text('tasks_completed');
            $table->text('skills_tools')->nullable();
            $table->text('lessons_learned')->nullable();
            $table->text('challenges_notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->string('attachment_mime_type', 100)->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->uuid('idempotency_key');
            $table->char('request_fingerprint', 64);
            $table->timestamps();

            $table->index(['user_id', 'activity_date']);
            $table->unique(['user_id', 'idempotency_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
