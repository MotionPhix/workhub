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
    Schema::create('work_entries', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->index();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->date('work_date');
      $table->text('description');
      $table->integer('hours_worked')->nullable();
      $table->json('tags')->nullable();
      $table->enum('status', ['draft', 'completed', 'in_progress'])->default('draft');
      $table->timestamps();
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
