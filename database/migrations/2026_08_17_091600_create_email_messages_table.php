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
        Schema::create('email_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('email_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('outreach_cycle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('outreach_assignment_id')->nullable()->constrained()->nullOnDelete();

            /* Inbound replies hang off the outbound message they answer. */
            $table->foreignUuid('parent_id')->nullable()->constrained('email_messages')->nullOnDelete();

            $table->string('direction')->default('outbound');
            $table->string('strategy')->default('cold');
            $table->string('status')->default('draft');

            $table->string('subject');
            $table->longText('body');

            /**
             * The variable values used to render this message, kept so the sent
             * email can always be reproduced exactly as it went out.
             *
             * @var array<string, string>
             */
            $table->json('variables')->nullable();

            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('reply_to')->nullable();

            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();

            $table->string('message_id')->nullable();
            $table->uuid('tracking_token')->nullable()->unique();
            $table->unsignedSmallInteger('opens')->default(0);

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'sent_at']);
            $table->index(['contact_id', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
