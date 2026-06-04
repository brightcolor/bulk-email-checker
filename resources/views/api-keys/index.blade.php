@extends('layouts.app')
@section('title', 'API Keys')
@section('page-title', 'API Keys')
@section('breadcrumbs')
    <li class="breadcrumb-item active">API Keys</li>
@endsection

@section('content')

@if(session('new_api_key'))
<div class="alert alert-success">
    <strong>Your new API Key (copy now — it will not be shown again):</strong><br>
    <code class="user-select-all">{{ session('new_api_key') }}</code>
</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Active API Keys</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr><th>Name</th><th>Prefix</th><th>Created By</th><th>Last Used</th><th>Expires</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($apiKeys as $key)
                        <tr>
                            <td>{{ $key->name }}</td>
                            <td><code>{{ $key->key_prefix }}...</code></td>
                            <td class="small">{{ $key->creator->name }}</td>
                            <td class="small text-muted">{{ $key->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="small">
                                @if($key->expires_at)
                                    @if($key->isExpired())
                                        <span class="badge bg-danger">Expired</span>
                                    @else
                                        {{ $key->expires_at->format('d.m.Y') }}
                                    @endif
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </td>
                            <td>
                                @can('delete', $key)
                                <form method="POST" action="{{ route('api-keys.destroy', $key) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"
                                            onclick="return confirm('Delete this API key? This cannot be undone.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-3 text-muted">No API keys yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @can('create', App\Models\ApiKey::class)
        <div class="card">
            <div class="card-header"><h3 class="card-title">Create API Key</h3></div>
            <form method="POST" action="{{ route('api-keys.store') }}">
                @csrf
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Key Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. Production API" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expires At <span class="text-muted">(optional)</span></label>
                        <input type="date" name="expires_at" class="form-control"
                               min="{{ now()->addDay()->format('Y-m-d') }}">
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Create Key
                    </button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Usage</h3></div>
            <div class="card-body">
                <p class="small text-muted">Include your key in every API request:</p>
                <pre class="small bg-light p-2 rounded"><code>Authorization: Bearer &lt;your-key&gt;</code></pre>
                <p class="small text-muted mt-2">Or as a header:</p>
                <pre class="small bg-light p-2 rounded"><code>X-Api-Key: &lt;your-key&gt;</code></pre>
                <p class="small text-muted mt-2">Base URL:</p>
                <pre class="small bg-light p-2 rounded"><code>{{ url('/api/v1') }}</code></pre>
            </div>
        </div>
        @endcan
    </div>
</div>
@endsection
