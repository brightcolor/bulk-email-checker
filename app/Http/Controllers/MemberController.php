<?php

namespace App\Http\Controllers;

use App\Models\TenantInvitation;
use App\Models\TenantMembership;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MemberController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index()
    {
        $tenant = app('current_tenant');
        Gate::authorize('invite', TenantMembership::class);

        $memberships = TenantMembership::where('tenant_id', $tenant->id)
            ->with('user')
            ->get();

        return view('members.index', compact('memberships', 'tenant'));
    }

    public function updateRole(Request $request, TenantMembership $membership)
    {
        Gate::authorize('updateRole', $membership);

        $data = $request->validate([
            'role' => ['required', 'in:owner,admin,member,viewer'],
        ]);

        $oldRole = $membership->role;
        $membership->update(['role' => $data['role']]);

        $tenant = app('current_tenant');
        $this->audit->log($tenant->id, 'member.role_changed', 'TenantMembership', $membership->id, [
            'user_id' => $membership->user_id,
            'old_role' => $oldRole,
            'new_role' => $data['role'],
        ]);

        return back()->with('success', 'Role updated.');
    }

    public function destroy(Request $request, TenantMembership $membership)
    {
        Gate::authorize('remove', $membership);

        $tenant = app('current_tenant');

        $this->audit->log($tenant->id, 'member.removed', 'TenantMembership', $membership->id, [
            'user_id' => $membership->user_id,
            'role' => $membership->role,
        ]);

        $membership->delete();

        return back()->with('success', 'Member removed.');
    }
}
