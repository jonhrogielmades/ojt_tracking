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
        Schema::create('weekly_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->text('summary');
            $table->text('student_notes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'changes_requested'])
                ->default('draft')
                ->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reviewer_comments')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'week_start']);
            $table->index(['user_id', 'status', 'week_start']);
        });

        Schema::table('dtr_entries', function (Blueprint $table) {
            $table->foreignId('weekly_submission_id')
                ->nullable()
                ->after('user_id')
                ->constrained('weekly_submissions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dtr_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('weekly_submission_id');
        });

        Schema::dropIfExists('weekly_submissions');
    }
};
