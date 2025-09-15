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
        Schema::create('work_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('work_title');
            $table->text('description');
            $table->dateTime('start_date_time');
            $table->dateTime('end_date_time');
            $table->string('project_uuid')->nullable();
            $table->enum('status', ['draft', 'completed', 'in_progress'])->default('draft');
            $table->timestamps();

            // Add foreign key constraint for project_uuid
            $table->foreign('project_uuid')->references('uuid')->on('projects')->nullOnDelete();

            // Add indexes for common queries
            $table->index(['user_id', 'start_date_time']);
            $table->index(['project_uuid', 'start_date_time']);
            $table->index(['status', 'start_date_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_entries');
    }
};
