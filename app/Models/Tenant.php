<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'created_by_user_id'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'tenant_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function bulkJobs(): HasMany
    {
        return $this->hasMany(BulkJob::class);
    }

    public function emailCheckResults(): HasMany
    {
        return $this->hasMany(EmailCheckResult::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(TenantSettings::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TenantInvitation::class);
    }

    public function getSettingsOrDefault(): TenantSettings
    {
        return $this->settings ?? new TenantSettings([
            'tenant_id' => $this->id,
            'smtp_checks_enabled' => config('verifier.smtp_enabled'),
            'catch_all_detection_enabled' => config('verifier.catch_all_enabled'),
            'max_upload_size_mb' => config('verifier.max_upload_size_mb'),
            'max_emails_per_job' => config('verifier.max_emails_per_job'),
        ]);
    }

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            if (empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });
    }
}
