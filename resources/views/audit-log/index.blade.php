@extends('layouts.app')
@section('title', 'Audit Log')
@section('page-title', 'Audit Log')
@section('breadcrumbs')
    <li class="breadcrumb-item active">Audit Log</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <h3 class="card-title flex-grow-1">Audit Log</h3>
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="action" class="form-control form-control-sm" placeholder="Filter action..."
                   value="{{ request('action') }}">
            <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
            @if(request()->hasAny(['action','user_id']))
                <a href="{{ route('audit-log.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Details</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="small text-muted text-nowrap">{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                        <td class="small">{{ $log->actor?->name ?? '<system>' }}</td>
                        <td><code class="small">{{ $log->action }}</code></td>
                        <td class="small text-muted">
                            @if($log->entity_type)
                                {{ class_basename($log->entity_type) }} #{{ $log->entity_id }}
                            @endif
                        </td>
                        <td class="small text-muted">
                            @if($log->metadata)
                                <span data-bs-toggle="tooltip" title="{{ json_encode($log->metadata) }}">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $log->ip_address }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-3 text-muted">No audit log entries.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
    <div class="card-footer">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
