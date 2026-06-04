<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSettings extends Model
{
    protected $fillable = [
        'tenant_id',
        'smtp_checks_enabled',
        'catch_all_detection_enabled',
        'max_upload_size_mb',
        'max_emails_per_job',
        'result_retention_days',
        'allow_exports',
        'default_job_visibility',
        'webhook_url',
    ];

    protected function casts(): array
    {
        return [
            'smtp_checks_enabled' => 'boolean',
            'catch_all_detection_enabled' => 'boolean',
            'allow_exports' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
