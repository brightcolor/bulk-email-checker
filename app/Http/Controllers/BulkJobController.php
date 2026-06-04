<?php

namespace App\Http\Controllers;

use App\Jobs\ParseBulkUpload;
use App\Models\BulkJob;
use App\Models\Team;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class BulkJobController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', BulkJob::class);

        $tenant = app('current_tenant');
        $user = $request->user();

        $query = BulkJob::forTenant($tenant->id)
            ->with(['creator', 'team'])
            ->orderByDesc('created_at');

        // Viewers and members only see their own or tenant-visible jobs
        if (!$user->isAdminOf($tenant->id)) {
            $query->where(function ($q) use ($user, $tenant) {
                $q->where('created_by_user_id', $user->id)
                    ->orWhereNull('team_id');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $jobs = $query->paginate(20)->withQueryString();

        return view('bulk-jobs.index', compact('jobs', 'tenant'));
    }

    public function create(Request $request)
    {
        Gate::authorize('create', BulkJob::class);

        $tenant = app('current_tenant');
        $teams = Team::where('tenant_id', $tenant->id)->get();

        return view('bulk-jobs.create', compact('teams', 'tenant'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', BulkJob::class);

        $tenant = app('current_tenant');
        $settings = $tenant->getSettingsOrDefault();

        $maxMb = $settings->max_upload_size_mb ?? config('verifier.max_upload_size_mb', 10);

        $data = $request->validate([
            'file' => ["required", "file", "mimes:txt,csv", "max:" . ($maxMb * 1024)],
            'name' => ['nullable', 'string', 'max:255'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        // Verify team belongs to this tenant
        if (!empty($data['team_id'])) {
            $team = Team::where('id', $data['team_id'])
                ->where('tenant_id', $tenant->id)
                ->firstOrFail();
        }

        $file = $request->file('file');
        $path = $file->store("uploads/{$tenant->id}", 'local');

        $job = BulkJob::create([
            'tenant_id' => $tenant->id,
            'created_by_user_id' => $request->user()->id,
            'team_id' => $data['team_id'] ?? null,
            'name' => $data['name'] ?? $file->getClientOriginalName(),
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'status' => 'pending',
        ]);

        $this->audit->log($tenant->id, 'bulk_job.created', 'BulkJob', $job->id, [
            'filename' => $job->original_filename,
        ]);

        ParseBulkUpload::dispatch($job->id);

        return redirect()->route('bulk-jobs.show', $job)
            ->with('success', 'Upload received. Processing will begin shortly.');
    }

    public function show(Request $request, BulkJob $bulkJob)
    {
        Gate::authorize('view', $bulkJob);

        $results = $bulkJob->results()
            ->orderByDesc('checked_at')
            ->paginate(50)
            ->withQueryString();

        return view('bulk-jobs.show', compact('bulkJob', 'results'));
    }

    public function destroy(Request $request, BulkJob $bulkJob)
    {
        Gate::authorize('delete', $bulkJob);

        $tenant = app('current_tenant');

        if ($bulkJob->file_path) {
            Storage::disk('local')->delete($bulkJob->file_path);
        }

        $bulkJob->delete();

        $this->audit->log($tenant->id, 'bulk_job.deleted', 'BulkJob', $bulkJob->id, [
            'filename' => $bulkJob->original_filename,
        ]);

        return redirect()->route('bulk-jobs.index')
            ->with('success', 'Job deleted.');
    }

    public function cancel(Request $request, BulkJob $bulkJob)
    {
        Gate::authorize('delete', $bulkJob);

        if ($bulkJob->isFinished()) {
            return back()->withErrors(['error' => 'Job is already finished.']);
        }

        $bulkJob->update(['status' => 'cancelled']);

        return back()->with('success', 'Job cancelled.');
    }

    public function export(Request $request, BulkJob $bulkJob)
    {
        Gate::authorize('export', $bulkJob);

        $tenant = app('current_tenant');

        $this->audit->log($tenant->id, 'export.downloaded', 'BulkJob', $bulkJob->id);

        $filename = 'results-job-' . $bulkJob->id . '-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($bulkJob) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'email', 'normalized_email', 'status', 'reason', 'domain',
                'suggested_correction', 'mx_valid', 'smtp_checked', 'smtp_valid',
                'is_disposable', 'is_role_account', 'is_catch_all', 'checked_at',
            ]);

            $bulkJob->results()
                ->orderBy('id')
                ->chunk(500, function ($results) use ($handle) {
                    foreach ($results as $result) {
                        $row = [
                            $this->sanitizeCsvValue($result->email),
                            $this->sanitizeCsvValue($result->normalized_email),
                            $result->status,
                            $this->sanitizeCsvValue($result->reason),
                            $this->sanitizeCsvValue($result->domain),
                            $this->sanitizeCsvValue($result->suggested_correction),
                            $result->mx_valid !== null ? ($result->mx_valid ? 'yes' : 'no') : '',
                            $result->smtp_checked ? 'yes' : 'no',
                            $result->smtp_valid !== null ? ($result->smtp_valid ? 'yes' : 'no') : '',
                            $result->is_disposable ? 'yes' : 'no',
                            $result->is_role_account ? 'yes' : 'no',
                            $result->is_catch_all ? 'yes' : 'no',
                            $result->checked_at?->toDateTimeString() ?? '',
                        ];
                        fputcsv($handle, $row);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function sanitizeCsvValue(?string $value): string
    {
        if ($value === null) return '';
        // Protect against CSV injection
        if (preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'" . $value;
        }
        return $value;
    }
}
