@extends('layouts.auth')
@section('title', 'Reset Password')
@section('content')
<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $email) }}" required>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label class="form-label">New Password</label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
</form>
@endsection
