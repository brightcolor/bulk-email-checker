<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('smtp_checks_enabled')->default(false);
            $table->boolean('catch_all_detection_enabled')->default(true);
            $table->unsignedInteger('max_upload_size_mb')->default(10);
            $table->unsignedInteger('max_emails_per_job')->default(50000);
            $table->unsignedInteger('result_retention_days')->default(90);
            $table->boolean('allow_exports')->default(true);
            $table->enum('default_job_visibility', ['tenant', 'team', 'private'])->default('tenant');
            $table->string('webhook_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
