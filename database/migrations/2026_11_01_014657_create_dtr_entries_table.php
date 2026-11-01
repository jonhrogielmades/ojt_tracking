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
        Schema::create('dtr_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->time('time_in');
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->time('time_out');
            $table->unsignedSmallInteger('total_minutes');
            $table->text('notes')->nullable();
            $table->uuid('idempotency_key');
            $table->char('request_fingerprint', 64);
            $table->timestamps();

            $table->unique(['user_id', 'work_date']);
            $table->unique(['user_id', 'idempotency_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dtr_entries');
    }
};
