@extends('layouts.app')
@section('title', 'Job Details')
@section('page-title', $bulkJob->name ?? $bulkJob->original_filename)
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('bulk-jobs.index') }}">Bulk Jobs</a></li>
    <li class="breadcrumb-item active">Job #{{ $bulkJob->id }}</li>
@endsection

@section('content')
<div class="row">
    {{-- Job Summary --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Job Summary</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Status</dt>
                    <dd class="col-7"><span class="badge bg-{{ $bulkJob->status_badge_class }}">{{ $bulkJob->status }}</span></dd>
                    <dt class="col-5">File</dt>
                    <dd class="col-7 text-break small">{{ $bulkJob->original_filename }}</dd>
                    <dt class="col-5">Team</dt>
                    <dd class="col-7">{{ $bulkJob->team?->name ?? '—' }}</dd>
                    <dt class="col-5">Created by</dt>
                    <dd class="col-7">{{ $bulkJob->creator?->name }}</dd>
                    <dt class="col-5">Started</dt>
                    <dd class="col-7 small">{{ $bulkJob->started_at?->format('d.m.Y H:i') ?? '—' }}</dd>
                    <dt class="col-5">Finished</dt>
                    <dd class="col-7 small">{{ $bulkJob->completed_at?->format('d.m.Y H:i') ?? '—' }}</dd>
                </dl>
                @if($bulkJob->error_message)
                    <div class="alert alert-warning mt-3 mb-0 small">
                        <i class="fas fa-exclamation-triangle me-1"></i> {{ $bulkJob->error_message }}
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Progress</h3></div>
            <div class="card-body">
                <div class="progress mb-2" style="height: 20px;">
                    <div class="progress-bar bg-{{ $bulkJob->status_badge_class }} progress-bar-striped @if($bulkJob->isRunning()) progress-bar-animated @endif"
                         role="progressbar"
                         style="width: {{ $bulkJob->progress_percent }}%">
                        {{ $bulkJob->progress_percent }}%
                    </div>
                </div>
                <div class="row text-center g-2 mt-1">
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <div class="fs-5 text-success fw-bold">{{ number_format($bulkJob->valid_count) }}</div>
                            <small class="text-muted">Valid</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <div class="fs-5 text-danger fw-bold">{{ number_format($bulkJob->invalid_count) }}</div>
                            <small class="text-muted">Invalid</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <div class="fs-5 text-warning fw-bold">{{ number_format($bulkJob->risky_count) }}</div>
                            <small class="text-muted">Risky</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <div class="fs-5 text-secondary fw-bold">{{ number_format($bulkJob->unknown_count) }}</div>
                            <small class="text-muted">Unknown</small>
                        </div>
                    </div>
                </div>
                <div class="mt-3 text-center text-muted small">
                    {{ number_format($bulkJob->processed_emails) }} / {{ number_format($bulkJob->total_emails) }} processed
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                @can('export', $bulkJob)
                <a href="{{ route('bulk-jobs.export', $bulkJob) }}" class="btn btn-success btn-sm">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </a>
                @endcan
                @can('delete', $bulkJob)
                @if($bulkJob->isRunning())
                <form method="POST" action="{{ route('bulk-jobs.cancel', $bulkJob) }}">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Cancel this job?')">
                        <i class="fas fa-stop me-1"></i> Cancel
                    </button>
                </form>
                @endif
                <form method="POST" action="{{ route('bulk-jobs.destroy', $bulkJob) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm"
                            onclick="return confirm('Delete this job and all results?')">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                </form>
                @endcan
            </div>
        </div>
    </div>

    {{-- Results Table --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title flex-grow-1">Results</h3>
                <small class="text-muted">{{ number_format($bulkJob->total_emails) }} total</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Reason</th>
                                <th>Suggestion</th>
                                <th>Flags</th>
                                <th>Checked</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($results as $result)
                            <tr>
                                <td class="font-monospace small text-break">
                                    {{ $result->normalized_email ?? $result->email }}
                                </td>
                                <td>
                                    <span class="badge badge-{{ $result->status }} bg-{{ $result->status_badge_class }}">
                                        {{ $result->status }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $result->reason }}</td>
                                <td class="small font-monospace">
                                    @if($result->suggested_correction)
                                        <span class="text-warning">{{ $result->suggested_correction }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($result->is_disposable) <span class="badge bg-dark" title="Disposable">D</span> @endif
                                    @if($result->is_role_account) <span class="badge bg-warning text-dark" title="Role">R</span> @endif
                                    @if($result->is_catch_all) <span class="badge bg-info text-dark" title="Catch-all">C</span> @endif
                                    @if($result->smtp_checked)
                                        @if($result->smtp_valid === true) <span class="badge bg-success" title="SMTP OK">S✓</span>
                                        @elseif($result->smtp_valid === false) <span class="badge bg-danger" title="SMTP Fail">S✗</span>
                                        @else <span class="badge bg-secondary" title="SMTP Unknown">S?</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $result->checked_at?->format('H:i:s') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">
                                @if($bulkJob->isRunning())
                                    <i class="fas fa-spinner fa-spin me-1"></i> Processing...
                                @else
                                    No results yet.
                                @endif
                            </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($results->hasPages())
            <div class="card-footer">
                {{ $results->links() }}
            </div>
            @endif
        </div>

        @if($bulkJob->isRunning())
        <div class="alert alert-info">
            <i class="fas fa-spinner fa-spin me-1"></i>
            This job is still running. Refresh the page to see updated results.
        </div>
        @endif
    </div>
</div>
@endsection
