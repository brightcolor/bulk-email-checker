<?php

namespace App\Jobs;

use App\Models\BulkJob;
use App\Models\EmailCheckResult;
use App\Services\Verifier\EmailVerifierService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessEmailCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(
        public readonly int $bulkJobId,
        public readonly string $email,
        public readonly int $tenantId,
    ) {}

    public function handle(EmailVerifierService $verifier): void
    {
        $job = BulkJob::find($this->bulkJobId);

        if (!$job || $job->status === 'cancelled') {
            return;
        }

        try {
            $result = $verifier->verify($this->email);

            $data = $result->toArray();
            $data['tenant_id'] = $this->tenantId;
            $data['bulk_job_id'] = $this->bulkJobId;

            EmailCheckResult::create($data);

            // Update counters atomically
            DB::transaction(function () use ($result, $job) {
                BulkJob::where('id', $this->bulkJobId)->update([
                    'processed_emails' => DB::raw('processed_emails + 1'),
                    $this->counterColumn($result->status) => DB::raw($this->counterColumn($result->status) . ' + 1'),
                ]);
            });

            // Check if job is now complete
            $job->refresh();
            if ($job->processed_emails >= $job->total_emails) {
                $this->markJobComplete($job);
            }
        } catch (\Exception $e) {
            Log::warning("Email check failed for {$this->email} in job #{$this->bulkJobId}: " . $e->getMessage());

            DB::table('bulk_jobs')->where('id', $this->bulkJobId)->update([
                'processed_emails' => DB::raw('processed_emails + 1'),
                'failed_count' => DB::raw('failed_count + 1'),
            ]);

            // Still check completion even on failure
            $job->refresh();
            if ($job->processed_emails >= $job->total_emails) {
                $this->markJobComplete($job);
            }
        }
    }

    private function counterColumn(string $status): string
    {
        return match ($status) {
            'valid' => 'valid_count',
            'invalid', 'syntax_error', 'mx_error' => 'invalid_count',
            'risky', 'role_account', 'catch_all' => 'risky_count',
            'disposable' => 'invalid_count',
            default => 'unknown_count',
        };
    }

    private function markJobComplete(BulkJob $job): void
    {
        $status = $job->failed_count > 0 ? 'completed_with_errors' : 'completed';

        $job->update([
            'status' => $status,
            'completed_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        DB::table('bulk_jobs')->where('id', $this->bulkJobId)->update([
            'processed_emails' => DB::raw('processed_emails + 1'),
            'failed_count' => DB::raw('failed_count + 1'),
        ]);
    }
}
