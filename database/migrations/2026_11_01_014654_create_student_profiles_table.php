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
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name')->nullable();
            $table->string('department')->nullable();
            $table->text('company_address')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->string('supervisor_email')->nullable();
            $table->string('supervisor_phone', 50)->nullable();
            $table->date('ojt_start_date')->nullable();
            $table->date('ojt_end_date')->nullable();
            $table->json('work_days')->nullable();
            $table->time('scheduled_time_in')->nullable();
            $table->time('scheduled_time_out')->nullable();
            $table->text('schedule_notes')->nullable();
            $table->unsignedInteger('required_minutes')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
