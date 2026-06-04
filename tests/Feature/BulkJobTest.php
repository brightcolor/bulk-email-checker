<?php

namespace Tests\Feature;

use App\Jobs\ParseBulkUpload;
use App\Models\BulkJob;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\TenantSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BulkJobTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'created_by_user_id' => $this->user->id,
        ]);
        TenantMembership::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
        ]);
        TenantSettings::create(['tenant_id' => $this->tenant->id]);

        Storage::fake('local');
        Bus::fake();
    }

    public function test_upload_creates_bulk_job(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'emails.csv',
            "email\nfoo@example.com\nbar@example.com\n"
        );

        $response = $this->actingAs($this->user)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post(route('bulk-jobs.store'), [
                'file' => $file,
                'name' => 'Test Job',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bulk_jobs', [
            'tenant_id' => $this->tenant->id,
            'created_by_user_id' => $this->user->id,
            'name' => 'Test Job',
        ]);

        Bus::assertDispatched(ParseBulkUpload::class);
    }

    public function test_bulk_job_belongs_to_correct_tenant(): void
    {
        $job = BulkJob::create([
            'tenant_id' => $this->tenant->id,
            'created_by_user_id' => $this->user->id,
            'original_filename' => 'test.csv',
            'status' => 'completed',
        ]);

        $this->assertEquals($this->tenant->id, $job->tenant_id);
    }

    public function test_export_returns_csv(): void
    {
        $job = BulkJob::create([
            'tenant_id' => $this->tenant->id,
            'created_by_user_id' => $this->user->id,
            'original_filename' => 'test.csv',
            'status' => 'completed',
            'total_emails' => 1,
        ]);

        \App\Models\EmailCheckResult::create([
            'tenant_id' => $this->tenant->id,
            'bulk_job_id' => $job->id,
            'email' => 'test@example.com',
            'normalized_email' => 'test@example.com',
            'domain' => 'example.com',
            'status' => 'valid',
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('bulk-jobs.export', $job));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('test@example.com', $response->streamedContent());
    }

    public function test_export_does_not_contain_other_tenant_data(): void
    {
        // Create a second tenant with its own job and results
        $user2 = User::factory()->create();
        $tenant2 = Tenant::create([
            'name' => 'Tenant 2',
            'slug' => 'tenant-2',
            'created_by_user_id' => $user2->id,
        ]);
        TenantMembership::create(['tenant_id' => $tenant2->id, 'user_id' => $user2->id, 'role' => 'owner']);
        TenantSettings::create(['tenant_id' => $tenant2->id]);

        $job2 = BulkJob::create([
            'tenant_id' => $tenant2->id,
            'created_by_user_id' => $user2->id,
            'original_filename' => 'other.csv',
            'status' => 'completed',
        ]);

        \App\Models\EmailCheckResult::create([
            'tenant_id' => $tenant2->id,
            'bulk_job_id' => $job2->id,
            'email' => 'secret@othertenant.com',
            'status' => 'valid',
            'checked_at' => now(),
        ]);

        // UserA tries to export job2 (belongs to tenant2)
        $response = $this->actingAs($this->user)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('bulk-jobs.export', $job2));

        $response->assertStatus(403);
    }
}
