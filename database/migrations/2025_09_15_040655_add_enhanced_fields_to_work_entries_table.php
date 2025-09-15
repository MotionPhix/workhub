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
        Schema::table('work_entries', function (Blueprint $table) {
            // Enhanced work logging fields
            $table->text('notes')->nullable()->after('description');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium')->after('status');
            $table->enum('work_type', ['task', 'meeting', 'call', 'email', 'travel', 'research', 'presentation', 'other'])->default('task')->after('priority');
            $table->string('location')->nullable()->after('work_type');

            // Sales & Marketing specific fields
            $table->json('contacts')->nullable()->after('location'); // Store contact information
            $table->json('organizations')->nullable()->after('contacts'); // Organizations visited/worked with
            $table->decimal('value_generated', 15, 2)->nullable()->after('organizations'); // Revenue/value from this work
            $table->enum('outcome', ['successful', 'partially_successful', 'unsuccessful', 'pending', 'follow_up_needed'])->nullable()->after('value_generated');

            // Additional tracking
            $table->json('attachments')->nullable()->after('outcome'); // File attachments metadata
            $table->string('mood')->nullable()->after('attachments'); // Mood/energy level during work
            $table->decimal('productivity_rating', 2, 1)->nullable()->after('mood'); // Self-rated productivity (1.0-5.0)
            $table->json('tools_used')->nullable()->after('productivity_rating'); // Tools/software used

            // Collaboration
            $table->json('collaborators')->nullable()->after('tools_used'); // Team members worked with
            $table->boolean('requires_follow_up')->default(false)->after('collaborators');
            $table->date('follow_up_date')->nullable()->after('requires_follow_up');

            // Weather impact (for field work, mood correlation)
            $table->string('weather_condition')->nullable()->after('follow_up_date');

            // Add indexes for better performance
            $table->index(['work_type', 'status']);
            $table->index(['priority', 'created_at']);
            $table->index(['requires_follow_up', 'follow_up_date']);
            $table->index(['outcome']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_entries', function (Blueprint $table) {
            $table->dropColumn([
                'notes',
                'priority',
                'work_type',
                'location',
                'contacts',
                'organizations',
                'value_generated',
                'outcome',
                'attachments',
                'mood',
                'productivity_rating',
                'tools_used',
                'collaborators',
                'requires_follow_up',
                'follow_up_date',
                'weather_condition',
            ]);
        });
    }
};
