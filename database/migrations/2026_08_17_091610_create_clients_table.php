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
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            /*
             * A client is a converted company or contact. Both links stay in
             * place so the full outreach history survives the conversion.
             */
            $table->foreignUuid('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('primary_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignUuid('opportunity_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('status')->default('active');
            $table->date('since');
            $table->decimal('total_revenue', 14, 2)->default(0);
            $table->decimal('retainer_amount', 14, 2)->nullable();
            $table->string('currency', 3)->default('MWK');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
