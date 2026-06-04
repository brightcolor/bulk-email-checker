<?php

namespace App\Policies;

use App\Models\TenantMembership;
use App\Models\User;

class TenantMembershipPolicy
{
    public function invite(User $user): bool
    {
        $tenant = app('current_tenant');
        return $user->isAdminOf($tenant->id);
    }

    public function updateRole(User $user, TenantMembership $membership): bool
    {
        $tenant = app('current_tenant');

        if ($membership->tenant_id !== $tenant->id) {
            return false;
        }

        // Only owner can change roles; admins cannot touch owners
        if ($user->isOwnerOf($tenant->id)) {
            return true;
        }

        if ($user->roleIn($tenant->id) === 'admin') {
            // Admins cannot demote/change owners
            return $membership->role !== 'owner';
        }

        return false;
    }

    public function remove(User $user, TenantMembership $membership): bool
    {
        $tenant = app('current_tenant');

        if ($membership->tenant_id !== $tenant->id) {
            return false;
        }

        // Prevent last owner from removing themselves
        if ($membership->user_id === $user->id && $membership->role === 'owner') {
            $ownerCount = TenantMembership::where('tenant_id', $tenant->id)
                ->where('role', 'owner')
                ->count();
            if ($ownerCount <= 1) {
                return false;
            }
        }

        if ($user->isOwnerOf($tenant->id)) {
            return true;
        }

        // Admin cannot remove owners
        if ($user->roleIn($tenant->id) === 'admin') {
            return $membership->role !== 'owner';
        }

        return false;
    }
}
