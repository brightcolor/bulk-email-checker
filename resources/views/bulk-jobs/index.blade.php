@extends('layouts.app')
@section('title', 'Bulk Jobs')
@section('page-title', 'Bulk Check Jobs')
@section('breadcrumbs')
    <li class="breadcrumb-item active">Bulk Jobs</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title flex-grow-1">Jobs</h3>
        @can('create', App\Models\BulkJob::class)
        <a href="{{ route('bulk-jobs.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> New Upload
        </a>
        @endcan
    </div>
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name / File</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th class="text-success">Valid</th>
                    <th class="text-danger">Invalid</th>
                    <th class="text-warning">Risky</th>
                    <th class="text-muted">Unknown</th>
                    <th>Team</th>
                    <th>By</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobs as $job)
                <tr>
                    <td class="text-muted small">{{ $job->id }}</td>
                    <td>
                        <a href="{{ route('bulk-jobs.show', $job) }}">
                            {{ $job->name ?? $job->original_filename }}
                        </a>
                        <br><small class="text-muted">{{ $job->original_filename }}</small>
                    </td>
                    <td>
                        <span class="badge bg-{{ $job->status_badge_class }}">{{ $job->status }}</span>
                    </td>
                    <td style="min-width: 120px;">
                        <div class="progress mb-1" style="height: 6px;">
                            <div class="progress-bar bg-{{ $job->status_badge_class }}"
                                 style="width: {{ $job->progress_percent }}%"></div>
                        </div>
                        <small class="text-muted">{{ $job->processed_emails }}/{{ $job->total_emails }}</small>
                    </td>
                    <td class="text-success">{{ number_format($job->valid_count) }}</td>
                    <td class="text-danger">{{ number_format($job->invalid_count) }}</td>
                    <td class="text-warning">{{ number_format($job->risky_count) }}</td>
                    <td class="text-muted">{{ number_format($job->unknown_count) }}</td>
                    <td>{{ $job->team?->name ?? '—' }}</td>
                    <td class="small">{{ $job->creator?->name }}</td>
                    <td class="small text-muted">{{ $job->created_at->diffForHumans() }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('bulk-jobs.show', $job) }}" class="btn btn-xs btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            @can('export', $job)
                            <a href="{{ route('bulk-jobs.export', $job) }}" class="btn btn-xs btn-success" title="Export CSV">
                                <i class="fas fa-file-csv"></i>
                            </a>
                            @endcan
                            @can('delete', $job)
                            @if($job->isRunning())
                            <form method="POST" action="{{ route('bulk-jobs.cancel', $job) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-warning" title="Cancel"
                                        onclick="return confirm('Cancel this job?')">
                                    <i class="fas fa-stop"></i>
                                </button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('bulk-jobs.destroy', $job) }}" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-danger" title="Delete"
                                        onclick="return confirm('Delete this job and all its results?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="text-center py-4 text-muted">
                        No jobs yet.
                        @can('create', App\Models\BulkJob::class)
                            <a href="{{ route('bulk-jobs.create') }}">Upload your first list.</a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($jobs->hasPages())
    <div class="card-footer">
        {{ $jobs->links() }}
    </div>
    @endif
</div>
@endsection
