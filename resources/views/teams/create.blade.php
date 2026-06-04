@extends('layouts.app')
@section('title', 'New Team')
@section('page-title', 'Create Team')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('teams.index') }}">Teams</a></li>
    <li class="breadcrumb-item active">New Team</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">New Team</h3></div>
            <form method="POST" action="{{ route('teams.store') }}">
                @csrf
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Team Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Team</button>
                    <a href="{{ route('teams.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
