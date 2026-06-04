@extends('layouts.app')
@section('title', $team->name)
@section('page-title', $team->name)
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('teams.index') }}">Teams</a></li>
    <li class="breadcrumb-item active">{{ $team->name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title flex-grow-1">Team Members</h3>
                @can('manageMembers', $team)
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                    <i class="fas fa-plus me-1"></i> Add Member
                </button>
                @endcan
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($members as $m)
                        <tr>
                            <td>{{ $m->user->name }}</td>
                            <td class="small text-muted">{{ $m->user->email }}</td>
                            <td><span class="badge bg-secondary">{{ $m->role }}</span></td>
                            <td>
                                @can('manageMembers', $team)
                                <form method="POST" action="{{ route('teams.members.remove', [$team, $m->user_id]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Remove?')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-3 text-muted">No members yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('update', $team)
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Edit Team</h3></div>
            <form method="POST" action="{{ route('teams.update', $team) }}">
                @csrf @method('PATCH')
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $team->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ $team->description }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
    @endcan
</div>

@can('manageMembers', $team)
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Member to {{ $team->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('teams.members.add', $team) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Member</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">— Select a workspace member —</option>
                            @foreach($tenantMembers as $tm)
                                @if(!$members->firstWhere('user_id', $tm->user_id))
                                <option value="{{ $tm->user_id }}">{{ $tm->user->name }} ({{ $tm->user->email }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role in Team</label>
                        <select name="role" class="form-select">
                            <option value="member">Member</option>
                            <option value="lead">Lead</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection
