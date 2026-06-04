@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
{{-- Info Cards --}}
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-info">
            <div class="inner">
                <h3>{{ number_format($stats->total_jobs) }}</h3>
                <p>Total Jobs</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
            </svg>
            <a href="{{ route('bulk-jobs.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                View Jobs <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3>{{ number_format($stats->total_valid) }}</h3>
                <p>Valid Addresses</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
            <a href="{{ route('bulk-jobs.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                View Jobs <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3>{{ number_format($stats->total_risky) }}</h3>
                <p>Risky / Role</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <a href="{{ route('bulk-jobs.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                View Jobs <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-danger">
            <div class="inner">
                <h3>{{ number_format($stats->total_invalid) }}</h3>
                <p>Invalid Addresses</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
            <a href="{{ route('bulk-jobs.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                View Jobs <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    {{-- Recent Jobs --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title flex-grow-1">Recent Jobs</h3>
                <a href="{{ route('bulk-jobs.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> New Upload
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Job</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Valid</th>
                            <th>Invalid</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentJobs as $job)
                        <tr>
                            <td>
                                <a href="{{ route('bulk-jobs.show', $job) }}">
                                    {{ $job->name ?? $job->original_filename }}
                                </a>
                                @if($job->team)
                                    <span class="badge bg-light text-dark ms-1">{{ $job->team->name }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $job->status_badge_class }}">{{ $job->status }}</span>
                            </td>
                            <td style="min-width: 100px;">
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-{{ $job->status_badge_class }}"
                                         style="width: {{ $job->progress_percent }}%"></div>
                                </div>
                                <small class="text-muted">{{ $job->progress_percent }}%</small>
                            </td>
                            <td><span class="text-success">{{ number_format($job->valid_count) }}</span></td>
                            <td><span class="text-danger">{{ number_format($job->invalid_count) }}</span></td>
                            <td class="text-muted small">{{ $job->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No jobs yet. <a href="{{ route('bulk-jobs.create') }}">Upload a list</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Workspace Info --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Workspace</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Name</dt>
                    <dd class="col-7">{{ $tenant->name }}</dd>
                    <dt class="col-5">Your Role</dt>
                    <dd class="col-7">
                        <span class="badge bg-primary">{{ app('current_membership')->role }}</span>
                    </dd>
                    <dt class="col-5">Total Checked</dt>
                    <dd class="col-7">{{ number_format($stats->total_emails_checked) }}</dd>
                    <dt class="col-5">Unknown</dt>
                    <dd class="col-7"><span class="text-muted">{{ number_format($stats->total_unknown) }}</span></dd>
                </dl>
            </div>
        </div>

        @if($failedJobs->isNotEmpty())
        <div class="card border-danger">
            <div class="card-header bg-danger text-white"><h3 class="card-title">Failed / Errors</h3></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($failedJobs as $job)
                    <li class="list-group-item">
                        <a href="{{ route('bulk-jobs.show', $job) }}" class="text-danger">
                            {{ $job->name ?? $job->original_filename }}
                        </a>
                        <br><small class="text-muted">{{ $job->status }} — {{ $job->created_at->diffForHumans() }}</small>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
