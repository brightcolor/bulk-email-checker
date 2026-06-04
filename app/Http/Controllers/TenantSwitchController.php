<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantSwitchController extends Controller
{
    public function select()
    {
        $user = Auth::user();
        $tenants = $user->tenants()->withPivot('role')->get();

        if ($tenants->count() === 1) {
            session(['active_tenant_id' => $tenants->first()->id]);
            return redirect()->route('dashboard');
        }

        return view('tenant.select', compact('tenants'));
    }

    public function switch(Request $request)
    {
        $request->validate(['tenant_id' => ['required', 'integer']]);

        $user = Auth::user();
        $tenantId = (int) $request->tenant_id;

        $membership = $user->membershipFor($tenantId);

        if (!$membership) {
            return back()->withErrors(['tenant' => 'Access denied.']);
        }

        session(['active_tenant_id' => $tenantId]);

        return redirect()->route('dashboard');
    }
}
