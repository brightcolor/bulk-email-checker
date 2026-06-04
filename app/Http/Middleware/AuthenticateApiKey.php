<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawKey = $request->bearerToken()
            ?? $request->header('X-Api-Key')
            ?? $request->input('api_key');

        if (!$rawKey) {
            return response()->json(['error' => 'API key required.'], 401);
        }

        $hash = hash('sha256', $rawKey);
        $apiKey = ApiKey::where('key_hash', $hash)
            ->whereNull('deleted_at')
            ->with('tenant')
            ->first();

        if (!$apiKey || $apiKey->isExpired()) {
            return response()->json(['error' => 'Invalid or expired API key.'], 401);
        }

        $apiKey->update(['last_used_at' => now()]);

        app()->instance('current_tenant', $apiKey->tenant);
        app()->instance('api_key', $apiKey);
        $request->merge(['_tenant' => $apiKey->tenant]);

        return $next($request);
    }
}
