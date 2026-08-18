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
        Schema::create('proposals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('proposal_template_id')->nullable()->constrained()->nullOnDelete();

            $table->string('number');
            $table->string('title');
            $table->string('project_name')->nullable();
            $table->string('status')->default('draft');
            $table->string('currency', 3)->default('MWK');
            $table->decimal('total_amount', 14, 2)->default(0);

            /* Points at the version currently considered "the" proposal. */
            $table->unsignedSmallInteger('current_version')->default(1);

            $table->date('valid_until')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            /* Token for the shareable read-only link and view tracking. */
            $table->uuid('public_token')->unique();
            $table->unsignedSmallInteger('view_count')->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'number']);
            $table->index(['user_id', 'status']);
            $table->index(['contact_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
