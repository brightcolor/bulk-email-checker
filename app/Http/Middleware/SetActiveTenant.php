<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetActiveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $tenantId = session('active_tenant_id');

        if (!$tenantId) {
            $tenants = $user->tenants()->get();

            if ($tenants->isEmpty()) {
                Auth::logout();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account has no tenant access.',
                ]);
            }

            if ($tenants->count() === 1) {
                $tenantId = $tenants->first()->id;
                session(['active_tenant_id' => $tenantId]);
            } else {
                return redirect()->route('tenant.select');
            }
        }

        // Verify the user is actually a member of this tenant (IDOR protection)
        $membership = $user->membershipFor((int) $tenantId);

        if (!$membership) {
            session()->forget('active_tenant_id');
            return redirect()->route('tenant.select')->withErrors([
                'tenant' => 'You do not have access to that workspace.',
            ]);
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            session()->forget('active_tenant_id');
            return redirect()->route('tenant.select');
        }

        // Attach to request for use in controllers
        $request->merge(['_tenant' => $tenant, '_membership' => $membership]);
        app()->instance('current_tenant', $tenant);
        app()->instance('current_membership', $membership);

        return $next($request);
    }
}
