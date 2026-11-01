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
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->foreignId('partner_company_id')
                ->nullable()
                ->after('user_id')
                ->constrained('partner_companies')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partner_company_id');
        });
    }
};
