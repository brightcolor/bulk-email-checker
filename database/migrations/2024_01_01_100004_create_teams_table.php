<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
