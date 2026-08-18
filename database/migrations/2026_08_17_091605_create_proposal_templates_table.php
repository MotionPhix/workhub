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
        Schema::create('proposal_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            /**
             * The ordered proposal sections.
             *
             * @var array<int, array{key: string, title: string, body: string, enabled: bool}>
             */
            $table->json('sections');

            $table->text('default_terms')->nullable();
            $table->string('currency', 3)->default('MWK');
            $table->string('status')->default('active');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_templates');
    }
};
