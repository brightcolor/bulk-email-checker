<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ParseBulkUpload;
use App\Models\BulkJob;
use App\Models\EmailCheckResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BulkJobApiController extends Controller
{
    public function index(): JsonResponse
    {
        $tenant = app('current_tenant');

        $jobs = BulkJob::forTenant($tenant->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($jobs);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = app('current_tenant');
        $settings = $tenant->getSettingsOrDefault();
        $maxMb = $settings->max_upload_size_mb ?? 10;

        $data = $request->validate([
            'file' => ["required", "file", "mimes:txt,csv", "max:" . ($maxMb * 1024)],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');
        $path = $file->store("uploads/{$tenant->id}", 'local');

        $job = BulkJob::create([
            'tenant_id' => $tenant->id,
            'created_by_user_id' => 0,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'name' => $data['name'] ?? $file->getClientOriginalName(),
            'status' => 'pending',
        ]);

        ParseBulkUpload::dispatch($job->id);

        return response()->json(['data' => $job], 201);
    }

    public function show(int $id): JsonResponse
    {
        $tenant = app('current_tenant');

        $job = BulkJob::where('id', $id)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        return response()->json(['data' => $job]);
    }

    public function results(Request $request, int $id): JsonResponse
    {
        $tenant = app('current_tenant');

        $job = BulkJob::where('id', $id)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $results = EmailCheckResult::where('bulk_job_id', $job->id)
            ->where('tenant_id', $tenant->id)
            ->paginate(100);

        return response()->json($results);
    }

    public function export(int $id)
    {
        $tenant = app('current_tenant');

        $job = BulkJob::where('id', $id)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $filename = 'results-job-' . $job->id . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($job) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'email', 'normalized_email', 'status', 'reason', 'domain',
                'suggested_correction', 'mx_valid', 'smtp_checked', 'smtp_valid',
                'is_disposable', 'is_role_account', 'is_catch_all', 'checked_at',
            ]);

            $job->results()->orderBy('id')->chunk(500, function ($results) use ($handle) {
                foreach ($results as $r) {
                    fputcsv($handle, [
                        $r->email, $r->normalized_email, $r->status, $r->reason,
                        $r->domain, $r->suggested_correction,
                        $r->mx_valid !== null ? ($r->mx_valid ? 'yes' : 'no') : '',
                        $r->smtp_checked ? 'yes' : 'no',
                        $r->smtp_valid !== null ? ($r->smtp_valid ? 'yes' : 'no') : '',
                        $r->is_disposable ? 'yes' : 'no',
                        $r->is_role_account ? 'yes' : 'no',
                        $r->is_catch_all ? 'yes' : 'no',
                        $r->checked_at?->toDateTimeString() ?? '',
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
