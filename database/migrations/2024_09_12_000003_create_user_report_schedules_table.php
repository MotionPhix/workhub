<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_report_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('report_type');
            $table->enum('frequency', ['daily', 'weekly', 'bi_weekly', 'monthly', 'quarterly']);
            $table->integer('day_of_week')->nullable(); // For weekly/bi-weekly
            $table->integer('day_of_month')->nullable(); // For monthly/quarterly
            $table->time('send_time');
            $table->string('timezone')->default('UTC');
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_generate')->default(true);
            $table->boolean('require_approval')->default(false);
            $table->json('recipient_emails')->nullable();
            $table->json('reminder_settings')->nullable();
            $table->json('template_preferences')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_due_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['next_due_at', 'is_active']);
            $table->index(['frequency', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_report_schedules');
    }
};
