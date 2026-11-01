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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('mfa_enabled')->default(false)->after('is_active');
            $table->string('mfa_code_hash')->nullable()->after('mfa_enabled');
            $table->timestamp('mfa_expires_at')->nullable()->after('mfa_code_hash');
            $table->timestamp('mfa_last_sent_at')->nullable()->after('mfa_expires_at');
            $table->softDeletes();
        });

        DB::table('users')
            ->where('role', 'admin')
            ->update(['role' => 'super_admin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('role', 'super_admin')
            ->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'mfa_enabled',
                'mfa_code_hash',
                'mfa_expires_at',
                'mfa_last_sent_at',
            ]);
        });
    }
};
