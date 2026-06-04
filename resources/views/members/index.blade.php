@extends('layouts.app')
@section('title', 'Members')
@section('page-title', 'Workspace Members')
@section('breadcrumbs')
    <li class="breadcrumb-item active">Members</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Members ({{ $memberships->count() }})</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Role</th><th>Since</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @foreach($memberships as $membership)
                        <tr>
                            <td>{{ $membership->user->name }}</td>
                            <td class="small text-muted">{{ $membership->user->email }}</td>
                            <td>
                                <form method="POST" action="{{ route('members.updateRole', $membership) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <select name="role" class="form-select form-select-sm w-auto d-inline"
                                            onchange="this.form.submit()"
                                            @cannot('updateRole', $membership) disabled @endcannot>
                                        @foreach(['owner','admin','member','viewer'] as $role)
                                            <option value="{{ $role }}" @selected($membership->role === $role)>{{ ucfirst($role) }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="small text-muted">{{ $membership->created_at->format('d.m.Y') }}</td>
                            <td>
                                @can('remove', $membership)
                                <form method="POST" action="{{ route('members.destroy', $membership) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"
                                            onclick="return confirm('Remove this member?')">
                                        <i class="fas fa-user-minus"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Invite Member</h3></div>
            <form method="POST" action="{{ route('invitations.store') }}">
                @csrf
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               placeholder="colleague@company.com" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="member">Member</option>
                            <option value="admin">Admin</option>
                            <option value="viewer">Viewer</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-envelope me-1"></i> Send Invitation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
