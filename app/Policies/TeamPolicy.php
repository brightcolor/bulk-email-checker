<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        $tenant = app('current_tenant');
        return $user->hasAccessTo($tenant->id);
    }

    public function create(User $user): bool
    {
        $tenant = app('current_tenant');
        return $user->isAdminOf($tenant->id);
    }

    public function update(User $user, Team $team): bool
    {
        $tenant = app('current_tenant');
        return $team->tenant_id === $tenant->id && $user->isAdminOf($tenant->id);
    }

    public function delete(User $user, Team $team): bool
    {
        $tenant = app('current_tenant');
        return $team->tenant_id === $tenant->id && $user->isAdminOf($tenant->id);
    }

    public function manageMembers(User $user, Team $team): bool
    {
        $tenant = app('current_tenant');
        return $team->tenant_id === $tenant->id && $user->isAdminOf($tenant->id);
    }
}
