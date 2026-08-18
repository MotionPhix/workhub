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
        Schema::create('proposal_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('proposal_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('status')->default('draft');

            /**
             * The rendered proposal content, frozen at the moment this version
             * was created. Versions are never edited in place.
             *
             * @var array<int, array{key: string, title: string, body: string, enabled: bool}>
             */
            $table->json('sections');

            /**
             * @var array<int, array{description: string, quantity: float, unit_price: float, total: float}>
             */
            $table->json('line_items')->nullable();

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('MWK');

            $table->text('timeline')->nullable();
            $table->text('terms')->nullable();
            $table->text('change_summary')->nullable();
            $table->date('valid_until')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['proposal_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_versions');
    }
};
