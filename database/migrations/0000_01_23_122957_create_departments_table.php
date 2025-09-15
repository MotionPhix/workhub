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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // Add UUID for department
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->decimal('working_hours_per_day', 4, 2)->default(8.00);
            $table->integer('daily_tasks_target')->default(5);
            $table->decimal('quality_target_percentage', 5, 2)->default(90.00);
            $table->decimal('allocated_hours', 10, 2)->default(0);
            $table->decimal('actual_hours', 10, 2)->default(0);
            $table->index(['allocated_hours', 'actual_hours']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
