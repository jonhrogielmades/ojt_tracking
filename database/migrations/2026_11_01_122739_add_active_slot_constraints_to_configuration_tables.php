<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('academic_terms', function (Blueprint $table) {
            $table->unsignedTinyInteger('active_slot')->nullable()->after('is_active');
            $table->unique('active_slot', 'academic_terms_single_active');
        });
        Schema::table('report_templates', function (Blueprint $table) {
            $table->unsignedTinyInteger('active_slot')->nullable()->after('is_active');
            $table->unique('active_slot', 'report_templates_single_active');
        });

        $this->normalizeActiveRows('academic_terms');
        $this->normalizeActiveRows('report_templates');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_terms', function (Blueprint $table) {
            $table->dropUnique('academic_terms_single_active');
            $table->dropColumn('active_slot');
        });
        Schema::table('report_templates', function (Blueprint $table) {
            $table->dropUnique('report_templates_single_active');
            $table->dropColumn('active_slot');
        });
    }

    private function normalizeActiveRows(string $table): void
    {
        $activeIds = DB::table($table)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->pluck('id');

        if ($activeIds->isEmpty()) {
            return;
        }

        DB::table($table)
            ->whereIn('id', $activeIds->skip(1))
            ->update(['is_active' => false, 'active_slot' => null]);
        DB::table($table)
            ->where('id', $activeIds->first())
            ->update(['active_slot' => 1]);
    }
};
