@extends('layouts.app')
@section('title', 'Invitations')
@section('page-title', 'Pending Invitations')
@section('breadcrumbs')
    <li class="breadcrumb-item active">Invitations</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Invitations</h3></div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr><th>Email</th><th>Role</th><th>Invited By</th><th>Expires</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($invitations as $inv)
                <tr>
                    <td>{{ $inv->email }}</td>
                    <td><span class="badge bg-secondary">{{ $inv->role }}</span></td>
                    <td class="small">{{ $inv->invitedBy->name }}</td>
                    <td class="small text-muted">{{ $inv->expires_at->format('d.m.Y') }}</td>
                    <td>
                        @if($inv->isAccepted())
                            <span class="badge bg-success">Accepted</span>
                        @elseif($inv->isExpired())
                            <span class="badge bg-danger">Expired</span>
                        @else
                            <span class="badge bg-warning text-dark">Pending</span>
                        @endif
                    </td>
                    <td>
                        @if($inv->isPending())
                        <form method="POST" action="{{ route('invitations.destroy', $inv) }}" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger"
                                    onclick="return confirm('Revoke this invitation?')">
                                <i class="fas fa-times"></i> Revoke
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-3 text-muted">No invitations.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
