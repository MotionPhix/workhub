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
        Schema::create('outreach_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('outreach_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('contact_id')->constrained()->cascadeOnDelete();

            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('rank')->default(0);
            $table->unsignedTinyInteger('score')->default(0);

            /*
             * Snapshot of why this prospect was selected, so the queue can
             * explain itself long after the ranking inputs have changed.
             *
             * @var array<int, string>
             */
            $table->json('reasons')->nullable();

            $table->string('strategy')->default('cold');
            $table->foreignUuid('suggested_service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->text('suggested_angle')->nullable();

            $table->date('assigned_on');
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->date('snoozed_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['outreach_cycle_id', 'contact_id'], 'assignments_cycle_contact_unique');
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'assigned_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outreach_assignments');
    }
};
