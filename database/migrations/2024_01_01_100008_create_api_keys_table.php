<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('key_hash')->unique();
            $table->string('key_prefix', 8);
            $table->json('permissions')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('key_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
