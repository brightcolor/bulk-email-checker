<?php

namespace App\Http\Controllers;

use App\Models\BulkJob;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('current_tenant');

        $stats = BulkJob::forTenant($tenant->id)
            ->selectRaw('
                COUNT(*) as total_jobs,
                COALESCE(SUM(total_emails), 0) as total_emails_checked,
                COALESCE(SUM(valid_count), 0) as total_valid,
                COALESCE(SUM(invalid_count), 0) as total_invalid,
                COALESCE(SUM(risky_count), 0) as total_risky,
                COALESCE(SUM(unknown_count), 0) as total_unknown
            ')
            ->first();

        $recentJobs = BulkJob::forTenant($tenant->id)
            ->with(['creator', 'team'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $failedJobs = BulkJob::forTenant($tenant->id)
            ->whereIn('status', ['failed', 'completed_with_errors'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard', compact('tenant', 'stats', 'recentJobs', 'failedJobs'));
    }
}
