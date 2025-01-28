<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('user_onboardings', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();

      $table->foreignUuid('user_uuid')
        ->references('uuid')
        ->on('users')
        ->cascadeOnDelete();

      $table->json('completed_steps');
      $table->boolean('is_completed')->default(false);
      $table->timestamp('completed_at')->nullable();
      $table->integer('current_step')->default(1);
      $table->decimal('completion_percentage', 5, 2)->default(0);
      $table->timestamps();
    });

    Schema::create('department_analytics', function (Blueprint $table) {
      $table->id();

      $table->foreignUuid('department_uuid')
        ->references('uuid')
        ->on('departments')
        ->cascadeOnDelete();

      $table->integer('allocated_hours')->default(0);
      $table->integer('actual_hours')->default(0);
      $table->integer('shared_projects')->default(0);
      $table->decimal('collaboration_score', 5, 2)->default(0);
      $table->json('performance_metrics')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('user_onboardings');
    Schema::dropIfExists('department_analytics');
  }
};
