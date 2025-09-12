<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->uuid('department_id')->nullable();
            $table->string('report_type');
            $table->string('title');
            $table->date('period_start');
            $table->date('period_end');
            $table->json('metrics_data')->nullable();
            $table->text('content')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'sent'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('sent_at')->nullable();
            $table->string('delivery_status')->nullable();
            $table->json('recipient_emails')->nullable();
            $table->string('template_version')->default('1.0');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('department_id')->references('uuid')->on('departments')->onDelete('set null');

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['report_type', 'created_at']);
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
