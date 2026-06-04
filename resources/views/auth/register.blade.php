@extends('layouts.auth')
@section('title', 'Register')
@section('content')
<form method="POST" action="{{ route('register') }}">
    @csrf
    <div class="mb-3">
        <label class="form-label">Full Name</label>
        <div class="input-group">
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" required autofocus>
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <div class="input-group">
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" required>
            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Workspace Name</label>
        <div class="input-group">
            <input type="text" name="tenant_name" class="form-control @error('tenant_name') is-invalid @enderror"
                   value="{{ old('tenant_name') }}" placeholder="My Company" required>
            <span class="input-group-text"><i class="fas fa-building"></i></span>
            @error('tenant_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <small class="text-muted">Creates your first workspace. You can create more later.</small>
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <div class="input-group">
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <div class="input-group">
            <input type="password" name="password_confirmation" class="form-control" required>
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
        </div>
    </div>
    <button type="submit" class="btn btn-primary w-100">Create Account</button>
    <div class="text-center mt-3">
        <a href="{{ route('login') }}">Already have an account? Sign in</a>
    </div>
</form>
@endsection
