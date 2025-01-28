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
    Schema::table('work_entries', function (Blueprint $table) {
      $table->foreignId('project_id')
        ->nullable()
        ->after('user_id')
        ->constrained()
        ->onDelete('set null');

      // Add index for common queries
      $table->index(['user_id', 'project_id', 'work_date']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('work_entries', function (Blueprint $table) {
      $table->dropForeign(['project_id']);
      $table->dropColumn('project_id');
      $table->dropIndex(['user_id', 'project_id', 'work_date']);
    });
  }
};
