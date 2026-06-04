<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function __construct(private readonly Request $request) {}

    public function log(
        int $tenantId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        array $metadata = [],
        ?int $actorUserId = null,
    ): void {
        AuditLog::create([
            'tenant_id' => $tenantId,
            'actor_user_id' => $actorUserId ?? Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata ?: null,
            'ip_address' => $this->request->ip(),
            'user_agent' => substr($this->request->userAgent() ?? '', 0, 255),
            'created_at' => now(),
        ]);
    }
}
