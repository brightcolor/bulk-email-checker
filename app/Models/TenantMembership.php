<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantMembership extends Model
{
    protected $fillable = ['tenant_id', 'user_id', 'role'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwner(): bool { return $this->role === 'owner'; }
    public function isAdmin(): bool { return in_array($this->role, ['owner', 'admin']); }
    public function isMember(): bool { return in_array($this->role, ['owner', 'admin', 'member']); }
    public function isViewer(): bool { return $this->role === 'viewer'; }
}
