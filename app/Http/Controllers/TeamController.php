<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\TenantMembership;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TeamController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index()
    {
        Gate::authorize('viewAny', Team::class);

        $tenant = app('current_tenant');
        $teams = Team::where('tenant_id', $tenant->id)
            ->withCount('members')
            ->with('creator')
            ->get();

        return view('teams.index', compact('teams', 'tenant'));
    }

    public function create()
    {
        Gate::authorize('create', Team::class);
        $tenant = app('current_tenant');
        return view('teams.create', compact('tenant'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Team::class);

        $tenant = app('current_tenant');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $team = Team::create([
            'tenant_id' => $tenant->id,
            'created_by_user_id' => $request->user()->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $this->audit->log($tenant->id, 'team.created', 'Team', $team->id, ['name' => $team->name]);

        return redirect()->route('teams.show', $team)
            ->with('success', 'Team created.');
    }

    public function show(Team $team)
    {
        Gate::authorize('viewAny', Team::class);

        $tenant = app('current_tenant');
        if ($team->tenant_id !== $tenant->id) abort(403);

        $members = $team->memberships()->with('user')->get();
        $tenantMembers = TenantMembership::where('tenant_id', $tenant->id)->with('user')->get();

        return view('teams.show', compact('team', 'members', 'tenantMembers', 'tenant'));
    }

    public function update(Request $request, Team $team)
    {
        Gate::authorize('update', $team);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $team->update($data);

        $tenant = app('current_tenant');
        $this->audit->log($tenant->id, 'team.updated', 'Team', $team->id, ['name' => $team->name]);

        return back()->with('success', 'Team updated.');
    }

    public function destroy(Request $request, Team $team)
    {
        Gate::authorize('delete', $team);

        $tenant = app('current_tenant');
        $this->audit->log($tenant->id, 'team.deleted', 'Team', $team->id, ['name' => $team->name]);

        $team->delete();

        return redirect()->route('teams.index')->with('success', 'Team deleted.');
    }

    public function addMember(Request $request, Team $team)
    {
        Gate::authorize('manageMembers', $team);

        $tenant = app('current_tenant');
        if ($team->tenant_id !== $tenant->id) abort(403);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', 'in:lead,member'],
        ]);

        // Must be a tenant member
        $isTenantMember = TenantMembership::where('tenant_id', $tenant->id)
            ->where('user_id', $data['user_id'])
            ->exists();

        if (!$isTenantMember) {
            return back()->withErrors(['user_id' => 'User is not a member of this workspace.']);
        }

        TeamMembership::updateOrCreate(
            ['team_id' => $team->id, 'user_id' => $data['user_id']],
            ['role' => $data['role']]
        );

        return back()->with('success', 'Member added to team.');
    }

    public function removeMember(Request $request, Team $team, int $userId)
    {
        Gate::authorize('manageMembers', $team);

        $tenant = app('current_tenant');
        if ($team->tenant_id !== $tenant->id) abort(403);

        TeamMembership::where('team_id', $team->id)->where('user_id', $userId)->delete();

        return back()->with('success', 'Member removed from team.');
    }
}
