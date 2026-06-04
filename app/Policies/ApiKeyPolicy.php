<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\User;

class ApiKeyPolicy
{
    public function viewAny(User $user): bool
    {
        $tenant = app('current_tenant');
        return $user->isAdminOf($tenant->id);
    }

    public function create(User $user): bool
    {
        $tenant = app('current_tenant');
        return $user->isAdminOf($tenant->id);
    }

    public function delete(User $user, ApiKey $apiKey): bool
    {
        $tenant = app('current_tenant');
        return $apiKey->tenant_id === $tenant->id
            && $user->isAdminOf($tenant->id);
    }
}
