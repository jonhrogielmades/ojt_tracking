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
        Schema::create('database_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 40)->default('local');
            $table->string('path')->unique();
            $table->string('database_driver', 40);
            $table->string('status', 40)->default('completed')->index();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('database_backups');
    }
};
