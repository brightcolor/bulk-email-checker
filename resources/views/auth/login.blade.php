@extends('layouts.auth')
@section('title', 'Login')
@section('content')
<form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="mb-3">
        <label class="form-label">Email</label>
        <div class="input-group">
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" required autofocus>
            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <div class="input-group">
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label" for="remember">Remember me</label>
        </div>
        <a href="{{ route('password.request') }}" class="text-muted small">Forgot password?</a>
    </div>
    <button type="submit" class="btn btn-primary w-100">Sign In</button>
    <div class="text-center mt-3">
        <a href="{{ route('register') }}">Don't have an account? Register</a>
    </div>
</form>
@endsection
