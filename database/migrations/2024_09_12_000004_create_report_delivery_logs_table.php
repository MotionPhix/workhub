<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_delivery_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_id');
            $table->string('recipient_email');
            $table->string('delivery_method')->default('email');
            $table->enum('status', ['pending', 'delivered', 'failed']);
            $table->timestamp('attempted_at');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('reports')->onDelete('cascade');

            $table->index(['report_id', 'status']);
            $table->index(['status', 'attempted_at']);
            $table->index(['retry_count', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_delivery_logs');
    }
};
