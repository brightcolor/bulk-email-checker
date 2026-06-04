<?php

namespace App\Services\Verifier;

use Illuminate\Support\Str;

class CatchAllDetector
{
    private int $timeout;
    private string $fromEmail;
    private string $fromDomain;

    public function __construct()
    {
        $this->timeout = config('verifier.smtp_timeout', 5);
        $this->fromEmail = config('verifier.smtp_from_email', 'verify@example.com');
        $this->fromDomain = config('verifier.smtp_from_domain', 'example.com');
    }

    public function check(VerificationResult $result): void
    {
        if (!config('verifier.catch_all_enabled', true)) {
            return;
        }

        if (!config('verifier.smtp_enabled', false)) {
            return;
        }

        if (empty($result->mxRecords)) {
            return;
        }

        $mxHost = $result->mxRecords[0]['host'] ?? null;
        if (!$mxHost) {
            return;
        }

        // Test with a random address on the same domain
        $testEmail = Str::random(20) . '@' . $result->domain;
        $isCatchAll = $this->testAddress($mxHost, $testEmail);

        if ($isCatchAll) {
            $result->isCatchAll = true;

            // Only downgrade if currently 'valid'
            if ($result->status === 'valid') {
                $result->status = 'catch_all';
                $result->reason = 'Domain accepts all email addresses (catch-all) — cannot verify individual mailbox';
            }
        }
    }

    private function testAddress(string $mxHost, string $testEmail): bool
    {
        try {
            $socket = @fsockopen($mxHost, 25, $errno, $errstr, $this->timeout);
            if (!$socket) {
                return false;
            }

            stream_set_timeout($socket, $this->timeout);

            $banner = fgets($socket, 1024);
            if (!str_starts_with($banner, '2')) {
                fclose($socket);
                return false;
            }

            fwrite($socket, "EHLO {$this->fromDomain}\r\n");
            $this->readUntilEnd($socket);

            fwrite($socket, "MAIL FROM:<{$this->fromEmail}>\r\n");
            $mailFrom = $this->readUntilEnd($socket);
            if (!str_starts_with($mailFrom, '2')) {
                fwrite($socket, "QUIT\r\n");
                fclose($socket);
                return false;
            }

            fwrite($socket, "RCPT TO:<{$testEmail}>\r\n");
            $rcpt = $this->readUntilEnd($socket);

            fwrite($socket, "QUIT\r\n");
            fclose($socket);

            return str_starts_with($rcpt, '2');
        } catch (\Exception) {
            return false;
        }
    }

    private function readUntilEnd($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 1024)) {
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        return trim(substr($response, 0, 3));
    }
}
