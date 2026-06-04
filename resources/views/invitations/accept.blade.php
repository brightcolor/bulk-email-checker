@extends('layouts.auth')
@section('title', 'Accept Invitation')
@section('content')
<p class="text-center">You've been invited to join</p>
<h4 class="text-center text-primary mb-3">{{ $invitation->tenant->name }}</h4>
<p class="text-center text-muted">as <span class="badge bg-secondary">{{ $invitation->role }}</span></p>

<form method="POST" action="{{ route('invitations.accept', $token) }}">
    @csrf
    <button type="submit" class="btn btn-success w-100">
        <i class="fas fa-check me-1"></i> Accept Invitation
    </button>
</form>
<div class="text-center mt-3">
    <a href="{{ route('login') }}" class="text-muted small">Cancel</a>
</div>
@endsection
