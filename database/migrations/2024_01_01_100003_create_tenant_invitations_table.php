<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('email');
            $table->enum('role', ['admin', 'member', 'viewer'])->default('member');
            $table->string('token_hash')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'email']);
            $table->index('token_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_invitations');
    }
};
