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
        Schema::create('contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('company_id')->nullable()->constrained()->nullOnDelete();

            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('facebook')->nullable();
            $table->string('twitter')->nullable();
            $table->string('instagram')->nullable();
            $table->string('location')->nullable();
            $table->string('industry')->nullable();

            $table->string('status')->default('new');
            $table->string('priority')->default('medium');
            $table->unsignedTinyInteger('lead_score')->default(0);
            $table->string('relationship_stage')->default('stranger');
            $table->string('lead_source')->nullable();
            $table->foreignUuid('primary_service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->text('potential_needs')->nullable();
            $table->decimal('estimated_budget', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_primary_contact')->default(false);

            /*
             * Outreach state. Maintained by the outreach services rather than
             * derived on read, because "mark as contacted" can happen without
             * an email being sent from the app.
             */
            $table->timestamp('first_contacted_at')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->unsignedSmallInteger('outreach_count')->default(0);
            $table->string('last_outcome')->nullable();
            $table->date('cooldown_until')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'last_contacted_at']);
            $table->index(['user_id', 'lead_score']);
            $table->index(['user_id', 'email']);
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
