<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'created_by_user_id', 'name',
        'key_hash', 'key_prefix', 'permissions',
        'last_used_at', 'expires_at',
    ];

    protected $hidden = ['key_hash'];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
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

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return !$this->isExpired() && $this->deleted_at === null;
    }

    public static function generateRaw(): string
    {
        return 'bec_' . Str::random(40);
    }

    public static function hashKey(string $rawKey): string
    {
        return hash('sha256', $rawKey);
    }

    public static function prefixFromKey(string $rawKey): string
    {
        return substr($rawKey, 0, 8);
    }
}
