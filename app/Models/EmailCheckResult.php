<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailCheckResult extends Model
{
    protected $fillable = [
        'tenant_id', 'bulk_job_id', 'email', 'normalized_email', 'domain',
        'status', 'reason', 'suggested_correction',
        'mx_valid', 'mx_records', 'smtp_checked', 'smtp_valid',
        'smtp_response_code', 'smtp_response_message',
        'is_disposable', 'is_role_account', 'is_catch_all',
        'raw_details', 'checked_at', 'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'mx_valid' => 'boolean',
            'mx_records' => 'array',
            'smtp_checked' => 'boolean',
            'smtp_valid' => 'boolean',
            'is_disposable' => 'boolean',
            'is_role_account' => 'boolean',
            'is_catch_all' => 'boolean',
            'raw_details' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bulkJob(): BelongsTo
    {
        return $this->belongsTo(BulkJob::class);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForJob(Builder $query, int $jobId): Builder
    {
        return $query->where('bulk_job_id', $jobId);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'valid' => 'success',
            'invalid', 'syntax_error', 'mx_error' => 'danger',
            'risky', 'role_account' => 'warning',
            'disposable' => 'dark',
            'catch_all' => 'info',
            'smtp_error', 'unknown' => 'secondary',
            default => 'secondary',
        };
    }
}
