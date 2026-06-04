<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('current_tenant');
        $user = $request->user();

        if (!$user->isAdminOf($tenant->id)) {
            abort(403, 'Only admins and owners can view the audit log.');
        }

        $query = AuditLog::forTenant($tenant->id)
            ->with('actor')
            ->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        if ($request->filled('user_id')) {
            $query->where('actor_user_id', $request->user_id);
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('audit-log.index', compact('logs', 'tenant'));
    }
}
