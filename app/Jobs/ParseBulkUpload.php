<?php

namespace App\Jobs;

use App\Models\BulkJob;
use App\Models\EmailCheckResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ParseBulkUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(public readonly int $bulkJobId) {}

    public function handle(): void
    {
        $job = BulkJob::find($this->bulkJobId);

        if (!$job) {
            Log::warning("ParseBulkUpload: BulkJob #{$this->bulkJobId} not found");
            return;
        }

        if ($job->status === 'cancelled') {
            return;
        }

        $job->update(['status' => 'parsing']);

        try {
            $emails = $this->extractEmails($job);

            if (empty($emails)) {
                $job->update([
                    'status' => 'failed',
                    'error_message' => 'No valid email addresses found in the uploaded file.',
                ]);
                return;
            }

            // Deduplicate (case-insensitive on domain)
            $emails = $this->deduplicate($emails);

            $settings = $job->tenant->getSettingsOrDefault();
            $maxEmails = $settings->max_emails_per_job ?? config('verifier.max_emails_per_job', 50000);

            if (count($emails) > $maxEmails) {
                $emails = array_slice($emails, 0, $maxEmails);
                $job->update([
                    'error_message' => "File truncated: only first {$maxEmails} addresses will be checked.",
                ]);
            }

            $job->update([
                'status' => 'queued',
                'total_emails' => count($emails),
            ]);

            // Dispatch individual check jobs
            foreach ($emails as $email) {
                ProcessEmailCheck::dispatch($job->id, $email, $job->tenant_id);
            }

            $job->update([
                'status' => 'running',
                'started_at' => now(),
            ]);

            // Optionally delete file after parsing to save storage
            // Storage::delete($job->file_path);
        } catch (\Exception $e) {
            Log::error("ParseBulkUpload failed for job #{$job->id}: " . $e->getMessage());
            $job->update([
                'status' => 'failed',
                'error_message' => 'File parsing failed: ' . $e->getMessage(),
            ]);
        }
    }

    private function extractEmails(BulkJob $job): array
    {
        $path = Storage::path($job->file_path);

        if (!file_exists($path)) {
            throw new \RuntimeException("Upload file not found: {$job->file_path}");
        }

        $extension = strtolower(pathinfo($job->file_path, PATHINFO_EXTENSION));
        $emails = [];

        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new \RuntimeException('Cannot open uploaded file');
        }

        $lineNumber = 0;
        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            $line = trim($line);

            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // For CSV: try to extract email from first or second column
            if ($extension === 'csv') {
                $columns = str_getcsv($line);
                foreach ($columns as $col) {
                    $candidate = trim($col);
                    if ($this->looksLikeEmail($candidate)) {
                        $emails[] = $candidate;
                        break;
                    }
                }
            } else {
                // TXT: one email per line (or multiple space/comma-separated)
                $candidates = preg_split('/[\s,;]+/', $line);
                foreach ($candidates as $candidate) {
                    $candidate = trim($candidate);
                    if ($this->looksLikeEmail($candidate)) {
                        $emails[] = $candidate;
                    }
                }
            }
        }

        fclose($handle);
        return $emails;
    }

    private function looksLikeEmail(string $value): bool
    {
        return str_contains($value, '@') && strlen($value) >= 3 && strlen($value) <= 320;
    }

    private function deduplicate(array $emails): array
    {
        $seen = [];
        $unique = [];

        foreach ($emails as $email) {
            // Normalize for dedup: lowercase domain, keep original local part case
            $normalized = $this->normalizeForDedup($email);
            if (!isset($seen[$normalized])) {
                $seen[$normalized] = true;
                $unique[] = $email;
            }
        }

        return $unique;
    }

    private function normalizeForDedup(string $email): string
    {
        if (!str_contains($email, '@')) {
            return strtolower($email);
        }
        [$local, $domain] = explode('@', $email, 2);
        return $local . '@' . strtolower($domain);
    }

    public function failed(\Throwable $exception): void
    {
        $job = BulkJob::find($this->bulkJobId);
        $job?->update([
            'status' => 'failed',
            'error_message' => 'Queue processing failed: ' . $exception->getMessage(),
        ]);
    }
}
