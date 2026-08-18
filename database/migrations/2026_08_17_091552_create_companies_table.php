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
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('industry')->nullable();
            $table->string('location')->nullable();
            $table->string('country')->nullable();
            $table->string('size')->nullable();
            $table->string('organization_type')->nullable();
            $table->string('lead_source')->nullable();

            $table->string('linkedin')->nullable();
            $table->string('facebook')->nullable();
            $table->string('twitter')->nullable();
            $table->string('instagram')->nullable();

            $table->decimal('estimated_budget', 12, 2)->nullable();
            $table->text('services_offered')->nullable();
            $table->text('notes')->nullable();

            /* Research fields, filled in while qualifying the organisation. */
            $table->text('description')->nullable();
            $table->text('what_they_do')->nullable();
            $table->string('website_quality')->nullable();
            $table->string('branding_quality')->nullable();
            $table->text('social_presence')->nullable();
            $table->text('potential_problems')->nullable();
            $table->text('opportunities')->nullable();
            $table->text('suggested_pitch')->nullable();
            $table->timestamp('researched_at')->nullable();

            $table->boolean('is_client')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'name']);
            $table->index(['user_id', 'industry']);
            $table->index(['user_id', 'is_client']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
