<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_check_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bulk_job_id')->constrained()->cascadeOnDelete();
            $table->string('email', 320);
            $table->string('normalized_email', 320)->nullable();
            $table->string('domain', 253)->nullable();
            $table->enum('status', [
                'valid', 'invalid', 'risky', 'unknown',
                'disposable', 'catch_all', 'role_account',
                'syntax_error', 'mx_error', 'smtp_error',
            ])->default('unknown');
            $table->string('reason')->nullable();
            $table->string('suggested_correction')->nullable();
            $table->boolean('mx_valid')->nullable();
            $table->json('mx_records')->nullable();
            $table->boolean('smtp_checked')->default(false);
            $table->boolean('smtp_valid')->nullable();
            $table->string('smtp_response_code', 10)->nullable();
            $table->string('smtp_response_message')->nullable();
            $table->boolean('is_disposable')->default(false);
            $table->boolean('is_role_account')->default(false);
            $table->boolean('is_catch_all')->default(false);
            $table->json('raw_details')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'bulk_job_id']);
            $table->index(['bulk_job_id', 'status']);
            $table->index('normalized_email');
            $table->index('domain');
            $table->index('status');
            $table->index('checked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_check_results');
    }
};
