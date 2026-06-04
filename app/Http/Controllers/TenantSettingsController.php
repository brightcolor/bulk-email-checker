<?php

namespace App\Http\Controllers;

use App\Models\TenantSettings;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TenantSettingsController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function edit()
    {
        Gate::authorize('view', TenantSettings::class);

        $tenant = app('current_tenant');
        $settings = $tenant->settings ?? new TenantSettings(['tenant_id' => $tenant->id]);

        return view('tenant-settings.edit', compact('tenant', 'settings'));
    }

    public function update(Request $request)
    {
        Gate::authorize('update', TenantSettings::class);

        $tenant = app('current_tenant');

        $data = $request->validate([
            'smtp_checks_enabled' => ['boolean'],
            'catch_all_detection_enabled' => ['boolean'],
            'max_upload_size_mb' => ['required', 'integer', 'min:1', 'max:100'],
            'max_emails_per_job' => ['required', 'integer', 'min:1', 'max:500000'],
            'result_retention_days' => ['required', 'integer', 'min:1', 'max:365'],
            'allow_exports' => ['boolean'],
            'default_job_visibility' => ['required', 'in:tenant,team,private'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
        ]);

        // Owners only can enable SMTP (sensitive)
        if (!$request->user()->isOwnerOf($tenant->id)) {
            unset($data['smtp_checks_enabled']);
        }

        $settings = TenantSettings::updateOrCreate(
            ['tenant_id' => $tenant->id],
            $data
        );

        $this->audit->log($tenant->id, 'tenant_settings.updated', 'TenantSettings', $settings->id);

        return back()->with('success', 'Settings saved.');
    }
}
