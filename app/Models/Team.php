<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes;

    protected $fillable = ['tenant_id', 'created_by_user_id', 'name', 'description'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TeamMembership::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function bulkJobs(): HasMany
    {
        return $this->hasMany(BulkJob::class);
    }

    public function hasMember(int $userId): bool
    {
        return $this->memberships()->where('user_id', $userId)->exists();
    }
}
