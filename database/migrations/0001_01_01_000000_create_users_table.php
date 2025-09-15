<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            $table->rememberToken();

            // Enhanced user metadata
            $table->foreignUuid('department_uuid')
                ->nullable()
                ->references('uuid')
                ->on('departments')
                ->nullOnDelete();

            $table->string('manager_email')->nullable();

            // Additional user fields
            $table->date('joined_at')->nullable();
            $table->json('settings')->nullable();
            $table->enum('gender', ['male', 'female', 'unknown'])->default('unknown');

            // Account status and security
            $table->boolean('is_active')->default(true);

            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();

            // Soft delete for account management
            $table->softDeletes();
            $table->timestamps();
        });

        // Keep existing password_reset_tokens and sessions tables
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->index(['user_id', 'token']);
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
