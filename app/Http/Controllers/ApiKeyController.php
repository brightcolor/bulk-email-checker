<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApiKeyController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index()
    {
        Gate::authorize('viewAny', ApiKey::class);

        $tenant = app('current_tenant');
        $apiKeys = ApiKey::where('tenant_id', $tenant->id)
            ->with('creator')
            ->orderByDesc('created_at')
            ->get();

        return view('api-keys.index', compact('apiKeys', 'tenant'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', ApiKey::class);

        $tenant = app('current_tenant');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $rawKey = ApiKey::generateRaw();

        $apiKey = ApiKey::create([
            'tenant_id' => $tenant->id,
            'created_by_user_id' => $request->user()->id,
            'name' => $data['name'],
            'key_hash' => ApiKey::hashKey($rawKey),
            'key_prefix' => ApiKey::prefixFromKey($rawKey),
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        $this->audit->log($tenant->id, 'api_key.created', 'ApiKey', $apiKey->id, [
            'name' => $apiKey->name,
            'prefix' => $apiKey->key_prefix,
        ]);

        // Show raw key only once
        return back()->with([
            'success' => 'API Key created. Copy it now — it will not be shown again.',
            'new_api_key' => $rawKey,
        ]);
    }

    public function destroy(Request $request, ApiKey $apiKey)
    {
        Gate::authorize('delete', $apiKey);

        $tenant = app('current_tenant');

        $this->audit->log($tenant->id, 'api_key.deleted', 'ApiKey', $apiKey->id, [
            'name' => $apiKey->name,
            'prefix' => $apiKey->key_prefix,
        ]);

        $apiKey->delete();

        return back()->with('success', 'API Key deleted.');
    }
}
