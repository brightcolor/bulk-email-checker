<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BulkJob extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'created_by_user_id', 'team_id', 'name',
        'original_filename', 'file_path', 'status',
        'total_emails', 'processed_emails',
        'valid_count', 'invalid_count', 'risky_count', 'unknown_count', 'failed_count',
        'error_message', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(EmailCheckResult::class);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->total_emails === 0) {
            return 0;
        }

        return (int) round(($this->processed_emails / $this->total_emails) * 100);
    }

    public function isRunning(): bool
    {
        return in_array($this->status, ['pending', 'parsing', 'queued', 'running']);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'completed_with_errors', 'failed', 'cancelled']);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'completed_with_errors' => 'warning',
            'failed' => 'danger',
            'cancelled' => 'secondary',
            'running' => 'info',
            'parsing', 'queued' => 'primary',
            default => 'light',
        };
    }
}
