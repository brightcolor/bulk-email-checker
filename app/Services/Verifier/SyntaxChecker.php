<?php

namespace App\Services\Verifier;

class SyntaxChecker
{
    public function check(VerificationResult $result): bool
    {
        $email = $result->email;

        // Basic structure check
        if (substr_count($email, '@') !== 1) {
            $result->status = 'syntax_error';
            $result->reason = 'Missing or multiple @ symbols';
            return false;
        }

        [$local, $domain] = explode('@', $email, 2);

        if (empty($local) || strlen($local) > 64) {
            $result->status = 'syntax_error';
            $result->reason = 'Local part invalid length';
            return false;
        }

        if (empty($domain) || strlen($domain) > 253) {
            $result->status = 'syntax_error';
            $result->reason = 'Domain part invalid length';
            return false;
        }

        // PHP filter_var for RFC-compatible check
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result->status = 'syntax_error';
            $result->reason = 'Failed RFC syntax validation';
            return false;
        }

        // No consecutive dots
        if (str_contains($email, '..')) {
            $result->status = 'syntax_error';
            $result->reason = 'Consecutive dots not allowed';
            return false;
        }

        // Domain must have at least one dot
        if (!str_contains($domain, '.')) {
            $result->status = 'syntax_error';
            $result->reason = 'Domain has no TLD';
            return false;
        }

        // Normalize
        $result->normalizedEmail = strtolower(trim($local)) . '@' . strtolower(trim($domain));
        $result->domain = strtolower(trim($domain));

        return true;
    }
}
