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
        Schema::create('outreach_cycles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('status')->default('planned');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedSmallInteger('target_count')->default(25);

            /* Groups the weekly cycles that belong to one monthly rotation. */
            $table->string('month_key', 7);
            $table->unsignedTinyInteger('week_of_month')->default(1);

            $table->text('notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'starts_on']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'month_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outreach_cycles');
    }
};
