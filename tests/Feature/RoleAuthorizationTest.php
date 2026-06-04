<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\TenantMembership;
use App\Models\TenantSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantWithRoles(): array
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $viewer = User::factory()->create();

        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'created_by_user_id' => $owner->id,
        ]);
        TenantSettings::create(['tenant_id' => $tenant->id]);

        TenantMembership::create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'role' => 'owner']);
        TenantMembership::create(['tenant_id' => $tenant->id, 'user_id' => $admin->id, 'role' => 'admin']);
        TenantMembership::create(['tenant_id' => $tenant->id, 'user_id' => $member->id, 'role' => 'member']);
        TenantMembership::create(['tenant_id' => $tenant->id, 'user_id' => $viewer->id, 'role' => 'viewer']);

        return compact('owner', 'admin', 'member', 'viewer', 'tenant');
    }

    public function test_viewer_cannot_create_bulk_job(): void
    {
        ['viewer' => $viewer, 'tenant' => $tenant] = $this->createTenantWithRoles();

        $response = $this->actingAs($viewer)
            ->withSession(['active_tenant_id' => $tenant->id])
            ->get(route('bulk-jobs.create'));

        $response->assertStatus(403);
    }

    public function test_member_cannot_invite_users(): void
    {
        ['member' => $member, 'tenant' => $tenant] = $this->createTenantWithRoles();

        $response = $this->actingAs($member)
            ->withSession(['active_tenant_id' => $tenant->id])
            ->get(route('members.index'));

        $response->assertStatus(403);
    }

    public function test_admin_cannot_remove_owner(): void
    {
        ['admin' => $admin, 'owner' => $owner, 'tenant' => $tenant] = $this->createTenantWithRoles();

        $ownerMembership = TenantMembership::where('tenant_id', $tenant->id)
            ->where('user_id', $owner->id)
            ->first();

        $response = $this->actingAs($admin)
            ->withSession(['active_tenant_id' => $tenant->id])
            ->delete(route('members.destroy', $ownerMembership));

        $response->assertStatus(403);
        $this->assertDatabaseHas('tenant_memberships', ['id' => $ownerMembership->id]);
    }

    public function test_owner_cannot_remove_themselves_as_last_owner(): void
    {
        ['owner' => $owner, 'tenant' => $tenant] = $this->createTenantWithRoles();

        $ownerMembership = TenantMembership::where('tenant_id', $tenant->id)
            ->where('user_id', $owner->id)
            ->first();

        $response = $this->actingAs($owner)
            ->withSession(['active_tenant_id' => $tenant->id])
            ->delete(route('members.destroy', $ownerMembership));

        $response->assertStatus(403);
        $this->assertDatabaseHas('tenant_memberships', ['id' => $ownerMembership->id]);
    }

    public function test_owner_can_invite_members(): void
    {
        ['owner' => $owner, 'tenant' => $tenant] = $this->createTenantWithRoles();

        $response = $this->actingAs($owner)
            ->withSession(['active_tenant_id' => $tenant->id])
            ->post(route('invitations.store'), [
                'email' => 'newuser@example.com',
                'role' => 'member',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenant_invitations', [
            'tenant_id' => $tenant->id,
            'email' => 'newuser@example.com',
        ]);
    }

    public function test_member_can_create_bulk_job(): void
    {
        ['member' => $member, 'tenant' => $tenant] = $this->createTenantWithRoles();

        // Just check the create page loads — actual file upload needs more setup
        $response = $this->actingAs($member)
            ->withSession(['active_tenant_id' => $tenant->id])
            ->get(route('bulk-jobs.create'));

        $response->assertStatus(200);
    }
}
