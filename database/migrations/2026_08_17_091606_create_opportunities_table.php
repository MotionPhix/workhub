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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('service_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->string('stage')->default('new');
            $table->decimal('value', 14, 2)->default(0);
            $table->string('currency', 3)->default('MWK');
            $table->unsignedTinyInteger('probability')->default(10);
            $table->date('expected_close_on')->nullable();
            $table->string('source')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->text('lost_reason')->nullable();
            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'stage']);
            $table->index(['user_id', 'expected_close_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
