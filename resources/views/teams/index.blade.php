@extends('layouts.app')
@section('title', 'Teams')
@section('page-title', 'Teams')
@section('breadcrumbs')
    <li class="breadcrumb-item active">Teams</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title flex-grow-1">Teams</h3>
                @can('create', App\Models\Team::class)
                <a href="{{ route('teams.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> New Team
                </a>
                @endcan
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr><th>Name</th><th>Members</th><th>Description</th><th>Created By</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($teams as $team)
                        <tr>
                            <td><a href="{{ route('teams.show', $team) }}">{{ $team->name }}</a></td>
                            <td><span class="badge bg-secondary">{{ $team->members_count }}</span></td>
                            <td class="text-muted small">{{ $team->description ?? '—' }}</td>
                            <td class="small">{{ $team->creator->name }}</td>
                            <td>
                                <a href="{{ route('teams.show', $team) }}" class="btn btn-xs btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('delete', $team)
                                <form method="POST" action="{{ route('teams.destroy', $team) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"
                                            onclick="return confirm('Delete team?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-3 text-muted">No teams yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
