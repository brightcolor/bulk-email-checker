@extends('layouts.auth')
@section('title', 'Invalid Invitation')
@section('content')
<div class="alert alert-danger text-center">
    <i class="fas fa-times-circle fa-2x mb-2 d-block"></i>
    This invitation link is invalid or has expired.
</div>
<div class="text-center">
    <a href="{{ route('login') }}" class="btn btn-primary">Go to Login</a>
</div>
@endsection
