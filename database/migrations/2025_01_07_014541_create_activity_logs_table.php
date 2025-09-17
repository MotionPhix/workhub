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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->integer('user_id')->nullable();
            $table->string('action');
            $table->text('description')->nullable();

            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Polymorphic relationship support
            $table->nullableMorphs('subject');

            // Additional context fields
            $table->json('metadata')->nullable();

            // Severity and categorization
            $table->enum('severity', [
                'info',
                'warning',
                'error',
                'critical',
            ])->default('info');

            // Geographical information
            $table->string('country')->nullable();
            $table->string('city')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexing for performance
            $table->index(['action', 'created_at']);
            $table->index(['severity', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
