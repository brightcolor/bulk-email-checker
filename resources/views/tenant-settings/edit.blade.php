@extends('layouts.app')
@section('title', 'Workspace Settings')
@section('page-title', 'Workspace Settings')
@section('breadcrumbs')
    <li class="breadcrumb-item active">Settings</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ $tenant->name }} — Settings</h3></div>
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                <div class="card-body">

                    <h5 class="mb-3">Verification Settings</h5>

                    @if(auth()->user()->isOwnerOf($tenant->id))
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="smtp_checks_enabled"
                               id="smtp_checks" value="1"
                               @checked($settings->smtp_checks_enabled ?? false)>
                        <label class="form-check-label" for="smtp_checks">
                            Enable SMTP Checks
                        </label>
                        <div class="form-text text-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            SMTP checks are slow and may be rate-limited by target servers. Use with caution.
                        </div>
                    </div>
                    @endif

                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="catch_all_detection_enabled"
                               id="catch_all" value="1"
                               @checked($settings->catch_all_detection_enabled ?? true)>
                        <label class="form-check-label" for="catch_all">Enable Catch-All Detection</label>
                    </div>

                    <hr>
                    <h5 class="mb-3">Upload Limits</h5>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Max Upload Size (MB)</label>
                                <input type="number" name="max_upload_size_mb" class="form-control"
                                       value="{{ $settings->max_upload_size_mb ?? 10 }}" min="1" max="100">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Max Emails per Job</label>
                                <input type="number" name="max_emails_per_job" class="form-control"
                                       value="{{ $settings->max_emails_per_job ?? 50000 }}" min="1" max="500000">
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3">Data Retention</h5>

                    <div class="mb-3">
                        <label class="form-label">Result Retention (days)</label>
                        <input type="number" name="result_retention_days" class="form-control"
                               value="{{ $settings->result_retention_days ?? 90 }}" min="1" max="365">
                        <div class="form-text">Results older than this will be eligible for deletion.</div>
                    </div>

                    <hr>
                    <h5 class="mb-3">Export & Visibility</h5>

                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="allow_exports" id="allow_exports" value="1"
                               @checked($settings->allow_exports ?? true)>
                        <label class="form-check-label" for="allow_exports">Allow Members to Export Results</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Default Job Visibility</label>
                        <select name="default_job_visibility" class="form-select">
                            <option value="tenant" @selected(($settings->default_job_visibility ?? 'tenant') === 'tenant')>Whole Workspace</option>
                            <option value="team" @selected(($settings->default_job_visibility ?? '') === 'team')>Team Only</option>
                            <option value="private" @selected(($settings->default_job_visibility ?? '') === 'private')>Private (Creator Only)</option>
                        </select>
                    </div>

                    <hr>
                    <h5 class="mb-3">Webhooks (optional)</h5>
                    <div class="mb-3">
                        <label class="form-label">Webhook URL</label>
                        <input type="url" name="webhook_url" class="form-control"
                               value="{{ $settings->webhook_url ?? '' }}" placeholder="https://...">
                        <div class="form-text text-muted">Not yet active — reserved for future use.</div>
                    </div>

                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
