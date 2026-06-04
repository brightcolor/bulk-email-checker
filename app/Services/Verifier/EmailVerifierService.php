<?php

namespace App\Services\Verifier;

class EmailVerifierService
{
    private SyntaxChecker $syntaxChecker;
    private DomainChecker $domainChecker;
    private DisposableChecker $disposableChecker;
    private RoleAccountChecker $roleAccountChecker;
    private TypoDetector $typoDetector;
    private SmtpChecker $smtpChecker;
    private CatchAllDetector $catchAllDetector;

    public function __construct()
    {
        $this->syntaxChecker = new SyntaxChecker();
        $this->domainChecker = new DomainChecker();
        $this->disposableChecker = new DisposableChecker();
        $this->roleAccountChecker = new RoleAccountChecker();
        $this->typoDetector = new TypoDetector();
        $this->smtpChecker = new SmtpChecker();
        $this->catchAllDetector = new CatchAllDetector();
    }

    public function verify(string $email): VerificationResult
    {
        $startTime = microtime(true);
        $result = new VerificationResult(trim($email));

        // Step 1: Syntax
        if (!$this->syntaxChecker->check($result)) {
            $result->durationMs = $this->elapsedMs($startTime);
            return $result;
        }

        // Step 2: Typo detection (always runs before MX, even if domain doesn't resolve)
        $this->typoDetector->check($result);

        // Step 3: Domain / MX
        if (!$this->domainChecker->check($result)) {
            $result->durationMs = $this->elapsedMs($startTime);
            return $result;
        }

        // Step 4: Disposable domain
        $this->disposableChecker->check($result);
        if ($result->status === 'disposable') {
            $result->durationMs = $this->elapsedMs($startTime);
            return $result;
        }

        // Step 5: Role account
        $this->roleAccountChecker->check($result);

        // Step 6: SMTP check (optional, per config)
        if (config('verifier.smtp_enabled', false)) {
            $this->smtpChecker->check($result);

            // Step 7: Catch-all detection (only with SMTP)
            $this->catchAllDetector->check($result);
        }

        // Determine final status if not already set to a specific value
        if ($result->status === 'unknown') {
            $result->status = $this->determineFinalStatus($result);
        }

        $result->durationMs = $this->elapsedMs($startTime);
        return $result;
    }

    private function determineFinalStatus(VerificationResult $result): string
    {
        if ($result->isDisposable) return 'disposable';
        if ($result->isCatchAll) return 'catch_all';
        if ($result->isRoleAccount) return 'role_account';

        if ($result->smtpChecked) {
            if ($result->smtpValid === true) return 'valid';
            if ($result->smtpValid === false) return 'invalid';
            return 'unknown'; // Greylisted or ambiguous
        }

        // Without SMTP: MX found = risky (we know the domain exists, but not the mailbox)
        if ($result->mxValid === true) {
            return 'risky';
        }

        return 'unknown';
    }

    private function elapsedMs(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }
}
