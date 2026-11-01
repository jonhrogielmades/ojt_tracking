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
        Schema::table('student_documents', function (Blueprint $table) {
            $table->foreignId('document_requirement_id')
                ->nullable()
                ->after('user_id')
                ->constrained('document_requirements')
                ->nullOnDelete();
            $table->string('review_status', 20)->default('pending')->after('size');
            $table->foreignId('reviewer_id')
                ->nullable()
                ->after('review_status')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('review_notes')->nullable()->after('reviewer_id');
            $table->timestamp('reviewed_at')->nullable()->after('review_notes');

            $table->index(['review_status', 'created_at']);
            $table->index(['document_requirement_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_documents', function (Blueprint $table) {
            $table->dropIndex(['review_status', 'created_at']);
            $table->dropIndex(['document_requirement_id', 'user_id']);
            $table->dropConstrainedForeignId('reviewer_id');
            $table->dropConstrainedForeignId('document_requirement_id');
            $table->dropColumn(['review_status', 'review_notes', 'reviewed_at']);
        });
    }
};
