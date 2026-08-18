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
        Schema::create('goals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->date('period_start');
            $table->date('period_end');

            $table->unsignedSmallInteger('prospect_target')->default(100);
            $table->unsignedSmallInteger('follow_up_target')->default(20);
            $table->unsignedSmallInteger('proposal_target')->default(10);
            $table->unsignedSmallInteger('client_target')->default(3);
            $table->decimal('revenue_target', 14, 2)->nullable();
            $table->string('currency', 3)->default('MWK');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'period_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
