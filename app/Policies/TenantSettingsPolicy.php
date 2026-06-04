<?php

namespace App\Policies;

use App\Models\User;

class TenantSettingsPolicy
{
    public function view(User $user): bool
    {
        $tenant = app('current_tenant');
        return $user->isAdminOf($tenant->id);
    }

    public function update(User $user): bool
    {
        $tenant = app('current_tenant');
        return $user->isAdminOf($tenant->id);
    }
}
