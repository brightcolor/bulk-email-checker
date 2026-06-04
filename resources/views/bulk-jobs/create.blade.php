@extends('layouts.app')
@section('title', 'New Bulk Upload')
@section('page-title', 'New Bulk Upload')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('bulk-jobs.index') }}">Bulk Jobs</a></li>
    <li class="breadcrumb-item active">New Upload</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Upload Email List</h3></div>
            <form method="POST" action="{{ route('bulk-jobs.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror"
                               accept=".csv,.txt" required>
                        <div class="form-text">Accepted formats: CSV, TXT. Max {{ $tenant->getSettingsOrDefault()->max_upload_size_mb }} MB.</div>
                        @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Job Name <span class="text-muted">(optional)</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="e.g. Newsletter cleanup June 2026">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    @if($teams->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label">Assign to Team <span class="text-muted">(optional)</span></label>
                        <select name="team_id" class="form-select @error('team_id') is-invalid @enderror">
                            <option value="">— No team —</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}" @selected(old('team_id') == $team->id)>{{ $team->name }}</option>
                            @endforeach
                        </select>
                        @error('team_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    @endif

                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Note:</strong> Email verification results are indicative only. SMTP checks may produce
                        <em>unknown</em> results for large providers, greylisting servers, and catch-all domains.
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i> Upload & Start Check
                    </button>
                    <a href="{{ route('bulk-jobs.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
