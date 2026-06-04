<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\TenantSettings;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'tenant_name' => ['required', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $slug = \Illuminate\Support\Str::slug($data['tenant_name']);
            $baseSlug = $slug;
            $i = 1;
            while (Tenant::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $i++;
            }

            $tenant = Tenant::create([
                'name' => $data['tenant_name'],
                'slug' => $slug,
                'created_by_user_id' => $user->id,
            ]);

            TenantMembership::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => 'owner',
            ]);

            TenantSettings::create(['tenant_id' => $tenant->id]);

            return $user;
        });

        Auth::login($user);

        $tenant = $user->tenants()->first();
        session(['active_tenant_id' => $tenant->id]);

        return redirect()->route('dashboard');
    }
}
