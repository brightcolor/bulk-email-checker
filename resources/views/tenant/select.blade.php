@extends('layouts.auth')
@section('title', 'Select Workspace')
@section('content')
<p class="text-center text-muted mb-3">You are a member of multiple workspaces. Choose one to continue.</p>
@foreach($tenants as $tenant)
<form method="POST" action="{{ route('tenant.switch') }}" class="mb-2">
    @csrf
    <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
    <button type="submit" class="btn btn-outline-primary w-100 text-start">
        <i class="fas fa-building me-2"></i>
        <strong>{{ $tenant->name }}</strong>
        <span class="badge bg-secondary float-end mt-1">{{ $tenant->pivot->role }}</span>
    </button>
</form>
@endforeach
<hr>
<div class="text-center">
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-link text-muted">Logout</button>
    </form>
</div>
@endsection
