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
        Schema::create('user_invites', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->unsignedBigInteger('invited_by');
            $table->string('email');
            $table->string('name');
            $table->uuid('department_uuid')->nullable();
            $table->string('manager_email')->nullable();
            $table->string('job_title')->nullable();
            $table->string('role_name')->default('employee');
            $table->string('token', 64)->unique();
            $table->timestamp('invited_at');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->integer('reminder_count')->default(0);
            $table->json('invite_data')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'cancelled'])->default('pending');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('invited_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('department_uuid')->references('uuid')->on('departments')->onDelete('set null');
            $table->foreign('manager_email')->references('email')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['email', 'status']);
            $table->index(['token', 'status', 'expires_at']);
            $table->index('expires_at');
            $table->index('invited_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_invites');
    }
};
