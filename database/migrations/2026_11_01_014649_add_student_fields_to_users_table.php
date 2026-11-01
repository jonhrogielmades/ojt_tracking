<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('student_id')->nullable()->unique()->after('name');
            $table->string('program')->nullable()->after('student_id');
            $table->string('section')->nullable()->after('program');
            $table->uuid('offline_storage_key')->nullable()->unique()->after('section');
        });

        DB::table('users')
            ->whereNull('offline_storage_key')
            ->select('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['offline_storage_key' => (string) Str::uuid()]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['student_id']);
            $table->dropUnique(['offline_storage_key']);
            $table->dropColumn(['student_id', 'program', 'section', 'offline_storage_key']);
        });
    }
};
