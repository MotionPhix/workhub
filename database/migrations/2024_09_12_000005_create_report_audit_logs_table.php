<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('report_id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action');
            $table->string('ip_address');
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->foreign('report_id')->references('id')->on('reports')->onDelete('cascade');

            $table->index(['report_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['created_at']); // For cleanup operations
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_audit_logs');
    }
};
