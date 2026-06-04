<?php

namespace App\Http\Controllers;

use App\Models\TenantInvitation;
use App\Models\TenantMembership;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index()
    {
        $tenant = app('current_tenant');
        Gate::authorize('invite', TenantMembership::class);

        $invitations = TenantInvitation::where('tenant_id', $tenant->id)
            ->with('invitedBy')
            ->orderByDesc('created_at')
            ->get();

        return view('invitations.index', compact('invitations', 'tenant'));
    }

    public function store(Request $request)
    {
        Gate::authorize('invite', TenantMembership::class);

        $tenant = app('current_tenant');

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:admin,member,viewer'],
        ]);

        // Check already a member
        $existingMembership = TenantMembership::where('tenant_id', $tenant->id)
            ->whereHas('user', fn($q) => $q->where('email', $data['email']))
            ->exists();

        if ($existingMembership) {
            return back()->withErrors(['email' => 'This user is already a member.']);
        }

        // Revoke any existing pending invitation for same email+tenant
        TenantInvitation::where('tenant_id', $tenant->id)
            ->where('email', $data['email'])
            ->whereNull('accepted_at')
            ->delete();

        $rawToken = Str::random(64);

        $invitation = TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'invited_by_user_id' => $request->user()->id,
            'email' => $data['email'],
            'role' => $data['role'],
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addDays(7),
        ]);

        $this->audit->log($tenant->id, 'member.invited', 'TenantInvitation', $invitation->id, [
            'email' => $data['email'],
            'role' => $data['role'],
        ]);

        // In production you'd mail the invitation link. For now, show it.
        $inviteUrl = route('invitations.accept', ['token' => $rawToken]);

        return back()->with('success', "Invitation sent. Link: {$inviteUrl}");
    }

    public function showAccept(Request $request, string $token)
    {
        $invitation = $this->findValidInvitation($token);

        if (!$invitation) {
            return view('invitations.invalid');
        }

        return view('invitations.accept', compact('invitation', 'token'));
    }

    public function accept(Request $request, string $token)
    {
        $invitation = $this->findValidInvitation($token);

        if (!$invitation) {
            return redirect()->route('login')->withErrors(['invitation' => 'Invitation is invalid or expired.']);
        }

        $user = Auth::user();

        if (!$user) {
            session(['invitation_token' => $token]);
            return redirect()->route('register')->with('info', 'Register to accept your invitation.');
        }

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            return back()->withErrors(['email' => 'This invitation was sent to a different email address.']);
        }

        // Already a member?
        $existing = TenantMembership::where('tenant_id', $invitation->tenant_id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $invitation->update(['accepted_at' => now()]);
            session(['active_tenant_id' => $invitation->tenant_id]);
            return redirect()->route('dashboard')->with('info', 'You are already a member of this workspace.');
        }

        TenantMembership::create([
            'tenant_id' => $invitation->tenant_id,
            'user_id' => $user->id,
            'role' => $invitation->role,
        ]);

        $invitation->update(['accepted_at' => now()]);

        $this->audit->log($invitation->tenant_id, 'invitation.accepted', 'TenantInvitation', $invitation->id, [
            'user_id' => $user->id,
        ]);

        session(['active_tenant_id' => $invitation->tenant_id]);

        return redirect()->route('dashboard')
            ->with('success', "Welcome to {$invitation->tenant->name}!");
    }

    public function destroy(Request $request, TenantInvitation $invitation)
    {
        Gate::authorize('invite', TenantMembership::class);

        $tenant = app('current_tenant');

        if ($invitation->tenant_id !== $tenant->id) {
            abort(403);
        }

        $invitation->delete();

        return back()->with('success', 'Invitation revoked.');
    }

    private function findValidInvitation(string $token): ?TenantInvitation
    {
        $hash = hash('sha256', $token);

        return TenantInvitation::where('token_hash', $hash)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with('tenant')
            ->first();
    }
}
