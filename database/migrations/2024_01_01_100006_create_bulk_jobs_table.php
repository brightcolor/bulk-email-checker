<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('original_filename');
            $table->string('file_path')->nullable();
            $table->enum('status', [
                'pending', 'parsing', 'queued', 'running',
                'completed', 'completed_with_errors', 'failed', 'cancelled',
            ])->default('pending');
            $table->unsignedInteger('total_emails')->default(0);
            $table->unsignedInteger('processed_emails')->default(0);
            $table->unsignedInteger('valid_count')->default(0);
            $table->unsignedInteger('invalid_count')->default(0);
            $table->unsignedInteger('risky_count')->default(0);
            $table->unsignedInteger('unknown_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_jobs');
    }
};
