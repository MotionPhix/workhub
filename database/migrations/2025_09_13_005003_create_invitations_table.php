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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('email');
            $table->string('token')->unique();
            $table->foreignId('invited_by')->constrained('users');
            $table->string('role')->default('employee');
            $table->uuid('department_uuid')->nullable();
            $table->string('manager_email')->nullable();
            $table->string('job_title')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->json('metadata')->nullable();
            $table->enum('status', ['pending', 'accepted', 'expired', 'cancelled'])->default('pending');
            $table->text('invitation_message')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['email', 'status']);
            $table->index('token');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
