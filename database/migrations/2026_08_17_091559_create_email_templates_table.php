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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('category');
            $table->string('subject');
            $table->text('body');

            /**
             * The variable names detected in the subject and body.
             *
             * @var array<int, string>
             */
            $table->json('variables')->nullable();

            $table->string('status')->default('draft');
            $table->text('notes')->nullable();

            /* Versioning: a duplicate keeps a pointer to the template it came from. */
            $table->unsignedSmallInteger('version')->default(1);
            $table->foreignUuid('parent_id')->nullable()->constrained('email_templates')->nullOnDelete();

            $table->unsignedInteger('times_used')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'category']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
