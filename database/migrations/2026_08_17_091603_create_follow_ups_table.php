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
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('email_message_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type')->default('email');
            $table->string('status')->default('pending');
            $table->string('priority')->default('medium');
            $table->date('due_on');

            $table->text('notes')->nullable();
            $table->text('outcome')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedTinyInteger('snooze_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status', 'due_on'], 'follow_ups_user_status_due_index');
            $table->index(['contact_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
