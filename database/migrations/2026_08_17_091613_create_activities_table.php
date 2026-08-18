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
        Schema::create('activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            /* The record the activity happened to: email, proposal, follow-up... */
            $table->nullableUuidMorphs('subject');

            /*
             * Contact and company are explicit relations rather than morph
             * lookups so a prospect timeline is a single indexed query.
             */
            $table->foreignUuid('contact_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('company_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('type');
            $table->string('description')->nullable();

            /**
             * Type specific detail, such as the from/to values of a status change.
             *
             * @var array<string, mixed>
             */
            $table->json('meta')->nullable();

            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
            $table->index(['contact_id', 'occurred_at']);
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
