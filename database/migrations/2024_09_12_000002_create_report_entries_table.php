<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_id');
            $table->uuid('work_entry_id')->nullable();
            $table->date('entry_date');
            $table->string('title');
            $table->text('description');
            $table->decimal('hours_worked', 5, 2);
            $table->json('metrics')->nullable();
            $table->json('tags')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('completion_status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('reports')->onDelete('cascade');
            $table->foreign('work_entry_id')->references('uuid')->on('work_entries')->onDelete('set null');

            $table->index(['report_id', 'entry_date']);
            $table->index(['completion_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_entries');
    }
};
