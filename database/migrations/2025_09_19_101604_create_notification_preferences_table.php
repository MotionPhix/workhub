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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Notification Types
            $table->boolean('task_assignments')->default(true);
            $table->boolean('task_updates')->default(true);
            $table->boolean('task_completions')->default(true);
            $table->boolean('project_updates')->default(true);
            $table->boolean('project_deadlines')->default(true);
            $table->boolean('team_updates')->default(true);
            $table->boolean('report_submissions')->default(true);
            $table->boolean('report_approvals')->default(true);
            $table->boolean('system_maintenance')->default(true);
            $table->boolean('security_alerts')->default(true);

            // Delivery Methods
            $table->boolean('email_notifications')->default(true);
            $table->boolean('browser_notifications')->default(true);
            $table->boolean('mobile_push_notifications')->default(false);

            // Timing Preferences
            $table->json('quiet_hours')->nullable(); // {start: "22:00", end: "08:00", timezone: "UTC"}
            $table->json('digest_frequency')->nullable(); // {daily: true, weekly: false, monthly: false}
            $table->boolean('weekend_notifications')->default(false);

            // Priority Levels
            $table->enum('minimum_priority', ['low', 'medium', 'high', 'urgent'])->default('low');

            // Advanced Settings
            $table->json('channel_preferences')->nullable(); // Different channels for different notification types
            $table->boolean('sound_enabled')->default(true);
            $table->string('sound_preference')->default('default');
            $table->boolean('vibration_enabled')->default(true);

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'uuid']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
