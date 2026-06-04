<?php

namespace App\Services\Verifier;

class DomainChecker
{
    public function check(VerificationResult $result): bool
    {
        $domain = $result->domain;

        // Try MX records first
        $mxRecords = [];
        $hasMx = @dns_get_record($domain, DNS_MX, $authoritative, $additional);

        if ($hasMx && count($hasMx) > 0) {
            usort($hasMx, fn($a, $b) => $a['pri'] <=> $b['pri']);
            $mxRecords = array_map(fn($r) => [
                'host' => $r['target'] ?? '',
                'priority' => $r['pri'] ?? 0,
            ], $hasMx);
            $result->mxValid = true;
            $result->mxRecords = $mxRecords;
            return true;
        }

        // Fallback: check A/AAAA records
        $hasA = @dns_get_record($domain, DNS_A);
        $hasAAAA = @dns_get_record($domain, DNS_AAAA);

        if (($hasA && count($hasA) > 0) || ($hasAAAA && count($hasAAAA) > 0)) {
            $result->mxValid = true;
            $result->rawDetails['mx_note'] = 'No MX records, but A/AAAA found — domain may accept mail';
            return true;
        }

        $result->mxValid = false;
        $result->status = 'mx_error';
        $result->reason = 'No MX or A records found for domain';
        return false;
    }
}
