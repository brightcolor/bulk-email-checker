<?php

namespace Tests\Feature;

use App\Models\BulkJob;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\TenantSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithTenant(string $role = 'owner'): array
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'name' => 'Tenant ' . $user->id,
            'slug' => 'tenant-' . $user->id,
            'created_by_user_id' => $user->id,
        ]);
        TenantMembership::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'role' => $role]);
        TenantSettings::create(['tenant_id' => $tenant->id]);
        return [$user, $tenant];
    }

    public function test_user_cannot_view_jobs_from_another_tenant(): void
    {
        [$userA, $tenantA] = $this->makeUserWithTenant();
        [$userB, $tenantB] = $this->makeUserWithTenant();

        $jobB = BulkJob::create([
            'tenant_id' => $tenantB->id,
            'created_by_user_id' => $userB->id,
            'original_filename' => 'test.csv',
            'status' => 'completed',
        ]);

        // UserA sets active tenant to tenantA
        $response = $this->actingAs($userA)
            ->withSession(['active_tenant_id' => $tenantA->id])
            ->get(route('bulk-jobs.show', $jobB));

        $response->assertStatus(403);
    }

    public function test_user_cannot_set_another_users_tenant_as_active(): void
    {
        [$userA, $tenantA] = $this->makeUserWithTenant();
        [$userB, $tenantB] = $this->makeUserWithTenant();

        // UserA tries to switch to tenantB (not a member)
        $response = $this->actingAs($userA)
            ->post(route('tenant.switch'), ['tenant_id' => $tenantB->id]);

        $response->assertSessionHasErrors();

        // Active tenant should NOT be tenantB
        $this->assertNotEquals($tenantB->id, session('active_tenant_id'));
    }

    public function test_middleware_rejects_invalid_tenant_in_session(): void
    {
        [$userA, $tenantA] = $this->makeUserWithTenant();
        [$userB, $tenantB] = $this->makeUserWithTenant();

        // UserA has tenantB's ID in session (manipulated)
        $response = $this->actingAs($userA)
            ->withSession(['active_tenant_id' => $tenantB->id])
            ->get(route('dashboard'));

        // Should redirect away (not show tenantB's data)
        $response->assertRedirect();
        $this->assertNotEquals(200, $response->status());
    }

    public function test_api_key_from_tenant_a_cannot_access_tenant_b_jobs(): void
    {
        [$userA, $tenantA] = $this->makeUserWithTenant();
        [$userB, $tenantB] = $this->makeUserWithTenant();

        $rawKey = \App\Models\ApiKey::generateRaw();
        \App\Models\ApiKey::create([
            'tenant_id' => $tenantA->id,
            'created_by_user_id' => $userA->id,
            'name' => 'Test Key',
            'key_hash' => \App\Models\ApiKey::hashKey($rawKey),
            'key_prefix' => \App\Models\ApiKey::prefixFromKey($rawKey),
        ]);

        $jobB = BulkJob::create([
            'tenant_id' => $tenantB->id,
            'created_by_user_id' => $userB->id,
            'original_filename' => 'test.csv',
            'status' => 'completed',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $rawKey])
            ->getJson('/api/v1/bulk-jobs/' . $jobB->id);

        $response->assertStatus(404);
    }
}
