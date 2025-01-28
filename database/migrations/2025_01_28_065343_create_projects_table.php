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
    Schema::create('projects', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->string('name');
      $table->text('description')->nullable();
      $table->uuid('department_uuid');
      $table->foreignId('manager_id')->constrained('users');
      $table->date('start_date');
      $table->date('due_date')->nullable();

      $table->enum(
        'status',
        ['draft', 'active', 'on_hold', 'completed', 'cancelled']
      )->default('draft');

      $table->enum(
        'priority',
        ['low', 'medium', 'high', 'urgent']
      )->default('medium');

      $table->integer('completion_percentage')->default(0);
      $table->boolean('is_shared')->default(false);
      $table->float('estimated_hours')->default(0);
      $table->float('actual_hours')->default(0);
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('department_uuid')
        ->references('uuid')
        ->on('departments')
        ->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('projects');
  }
};
