@extends('layouts.auth')
@section('title', 'Forgot Password')
@section('content')
<p class="text-muted text-center mb-3">Enter your email to receive a password reset link.</p>
<form method="POST" action="{{ route('password.email') }}">
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
    <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
    <div class="text-center mt-3">
        <a href="{{ route('login') }}">Back to login</a>
    </div>
</form>
@endsection
