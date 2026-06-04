<?php

namespace App\Services\Verifier;

class SmtpChecker
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
        if (!config('verifier.smtp_enabled', false)) {
            return;
        }

        $mxHost = $this->getMxHost($result->mxRecords);

        if (!$mxHost) {
            return;
        }

        $result->smtpChecked = true;
        $startTime = microtime(true);

        try {
            $response = $this->performSmtpCheck($mxHost, $result->normalizedEmail);
            $result->rawDetails['smtp'] = $response;

            $code = (int) substr($response['code'] ?? '0', 0, 1);
            $fullCode = $response['code'] ?? '';

            $result->smtpResponseCode = $fullCode;
            $result->smtpResponseMessage = $response['message'] ?? '';

            if ($fullCode === '250') {
                $result->smtpValid = true;
            } elseif (str_starts_with($fullCode, '5')) {
                // 5xx = permanent rejection
                $result->smtpValid = false;
            } else {
                // Greylisting (451), tarpitting, or ambiguous
                $result->smtpValid = null;
                $result->rawDetails['smtp_note'] = 'Ambiguous SMTP response — result is unknown';
            }
        } catch (\Exception $e) {
            $result->rawDetails['smtp_error'] = $e->getMessage();
            $result->smtpValid = null;
        }
    }

    private function performSmtpCheck(string $mxHost, string $email): array
    {
        $socket = @fsockopen($mxHost, 25, $errno, $errstr, $this->timeout);

        if (!$socket) {
            // Try port 587 as fallback
            $socket = @fsockopen($mxHost, 587, $errno, $errstr, $this->timeout);
        }

        if (!$socket) {
            throw new \RuntimeException("Cannot connect to {$mxHost}: {$errstr}");
        }

        stream_set_timeout($socket, $this->timeout);

        $response = fgets($socket, 1024);
        if (!str_starts_with($response, '2')) {
            fclose($socket);
            throw new \RuntimeException("SMTP server not ready: {$response}");
        }

        fwrite($socket, "EHLO {$this->fromDomain}\r\n");
        $this->readResponse($socket);

        fwrite($socket, "MAIL FROM:<{$this->fromEmail}>\r\n");
        $mailFromResponse = $this->readResponse($socket);

        if (!str_starts_with($mailFromResponse['code'], '2')) {
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            return $mailFromResponse;
        }

        fwrite($socket, "RCPT TO:<{$email}>\r\n");
        $rcptResponse = $this->readResponse($socket);

        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return $rcptResponse;
    }

    private function readResponse($socket): array
    {
        $response = '';
        while ($line = fgets($socket, 1024)) {
            $response .= $line;
            // Last line of multi-line response has space after code
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }

        return [
            'code' => substr($response, 0, 3),
            'message' => trim(substr($response, 4)),
            'full' => $response,
        ];
    }

    private function getMxHost(array $mxRecords): ?string
    {
        if (empty($mxRecords)) {
            return null;
        }

        return $mxRecords[0]['host'] ?? null;
    }
}
