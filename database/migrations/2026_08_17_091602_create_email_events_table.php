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
        Schema::create('email_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('email_message_id')->constrained()->cascadeOnDelete();

            $table->string('type');
            $table->timestamp('occurred_at');

            /**
             * Transport detail for the event, such as the failure message or
             * the user agent that opened the email.
             *
             * @var array<string, mixed>
             */
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['email_message_id', 'occurred_at'], 'email_events_message_occurred_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_events');
    }
};
