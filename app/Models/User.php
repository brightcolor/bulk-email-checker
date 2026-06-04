<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenantMemberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'tenant_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMembership::class);
    }

    public function bulkJobs(): HasMany
    {
        return $this->hasMany(BulkJob::class, 'created_by_user_id');
    }

    public function membershipFor(int $tenantId): ?TenantMembership
    {
        return $this->tenantMemberships()->where('tenant_id', $tenantId)->first();
    }

    public function roleIn(int $tenantId): ?string
    {
        return $this->membershipFor($tenantId)?->role;
    }

    public function isOwnerOf(int $tenantId): bool
    {
        return $this->roleIn($tenantId) === 'owner';
    }

    public function isAdminOf(int $tenantId): bool
    {
        return in_array($this->roleIn($tenantId), ['owner', 'admin']);
    }

    public function isMemberOf(int $tenantId): bool
    {
        return in_array($this->roleIn($tenantId), ['owner', 'admin', 'member']);
    }

    public function hasAccessTo(int $tenantId): bool
    {
        return $this->roleIn($tenantId) !== null;
    }
}
