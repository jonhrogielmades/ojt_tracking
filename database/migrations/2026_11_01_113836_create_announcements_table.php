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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('audience', 20)->default('all');
            $table->string('audience_value')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['audience', 'audience_value']);
            $table->index(['published_at', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
