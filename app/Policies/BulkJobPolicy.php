<?php

namespace App\Policies;

use App\Models\BulkJob;
use App\Models\User;

class BulkJobPolicy
{
    public function viewAny(User $user): bool
    {
        $tenant = app('current_tenant');
        return $user->hasAccessTo($tenant->id);
    }

    public function view(User $user, BulkJob $job): bool
    {
        $tenant = app('current_tenant');

        if ($job->tenant_id !== $tenant->id) {
            return false;
        }

        $role = $user->roleIn($tenant->id);

        if (in_array($role, ['owner', 'admin'])) {
            return true;
        }

        // If job belongs to a team, check team membership
        if ($job->team_id !== null) {
            return $job->team->hasMember($user->id) || $role === 'member';
        }

        return in_array($role, ['member', 'viewer']);
    }

    public function create(User $user): bool
    {
        $tenant = app('current_tenant');
        return $user->isMemberOf($tenant->id);
    }

    public function delete(User $user, BulkJob $job): bool
    {
        $tenant = app('current_tenant');

        if ($job->tenant_id !== $tenant->id) {
            return false;
        }

        return $user->isAdminOf($tenant->id)
            || $job->created_by_user_id === $user->id;
    }

    public function export(User $user, BulkJob $job): bool
    {
        $tenant = app('current_tenant');

        if ($job->tenant_id !== $tenant->id) {
            return false;
        }

        $settings = $tenant->getSettingsOrDefault();
        if (!$settings->allow_exports) {
            return $user->isAdminOf($tenant->id);
        }

        return $this->view($user, $job);
    }
}
