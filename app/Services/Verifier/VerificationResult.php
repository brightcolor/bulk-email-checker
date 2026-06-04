<?php

namespace App\Services\Verifier;

class VerificationResult
{
    public string $email;
    public string $normalizedEmail = '';
    public string $domain = '';
    public string $status = 'unknown';
    public string $reason = '';
    public ?string $suggestedCorrection = null;
    public ?bool $mxValid = null;
    public array $mxRecords = [];
    public bool $smtpChecked = false;
    public ?bool $smtpValid = null;
    public ?string $smtpResponseCode = null;
    public ?string $smtpResponseMessage = null;
    public bool $isDisposable = false;
    public bool $isRoleAccount = false;
    public bool $isCatchAll = false;
    public array $rawDetails = [];
    public int $durationMs = 0;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'normalized_email' => $this->normalizedEmail,
            'domain' => $this->domain,
            'status' => $this->status,
            'reason' => $this->reason,
            'suggested_correction' => $this->suggestedCorrection,
            'mx_valid' => $this->mxValid,
            'mx_records' => $this->mxRecords,
            'smtp_checked' => $this->smtpChecked,
            'smtp_valid' => $this->smtpValid,
            'smtp_response_code' => $this->smtpResponseCode,
            'smtp_response_message' => $this->smtpResponseMessage,
            'is_disposable' => $this->isDisposable,
            'is_role_account' => $this->isRoleAccount,
            'is_catch_all' => $this->isCatchAll,
            'raw_details' => $this->rawDetails ?: null,
            'duration_ms' => $this->durationMs,
            'checked_at' => now()->toDateTimeString(),
        ];
    }
}
